<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ActivityForm;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\RfqQuote;
use App\Models\RfqQuoteItem;
use App\Models\Vendor;
use App\Services\NotificationService;
use App\Support\DocumentBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RfqController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $query = Rfq::with(['activityForm', 'quotes', 'createdBy'])->latest();
        \App\Support\RecordVisibility::apply($query, Auth::user());

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('rfq_number', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%");
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $rfqs = $query->paginate(20);
        return view('rfq.index', compact('rfqs'));
    }

    public function create(Request $request): View
    {
        $preparationRfq = $request->rfq_id ? Rfq::where('status','draft')->whereNotNull('handoff_activity_id')->findOrFail($request->rfq_id) : null;
        if ($preparationRfq) abort_unless(\App\Support\RecordVisibility::apply(Rfq::query(), Auth::user())->whereKey($preparationRfq->id)->exists(),403);
        $approvedActivities = collect();
        $budgetOptions = \App\Models\DepartmentBudget::active()->with('department')->orderBy('activity_title')->get();
        $catalogRates = \App\Models\VendorRate::available()->with('vendor')->orderBy('item_name')->get();
        $vendors = Vendor::where('is_active', true)->whereNotNull('email')->orderBy('name')->get();
        $selectedActivity = $preparationRfq?->activityForm;

        $activityPayload = $approvedActivities->mapWithKeys(function (ActivityForm $activity) {
            return [$activity->id => [
                'title' => $activity->activity_name,
                'items' => $activity->lineItems->map(function ($item) {
                    return [
                        'source_line_item_id' => $item->id,
                    'description' => $item->item_name,
                        'quantity' => (float) $item->quantity,
                        'unit' => $item->unit,
                        'request_remarks' => $item->item_remarks,
                    ];
                })->values()->all(),
            ]];
        })->all();
        $initialItems = old('items', $selectedActivity
            ? $selectedActivity->lineItems->map(function ($item) {
                return [
                    'source_line_item_id' => $item->id,
                    'description' => $item->item_name,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'request_remarks' => $item->item_remarks,
                    'vendor_ids' => [],
                ];
            })->values()->all()
            : [['description' => '', 'quantity' => 1, 'unit' => 'pcs', 'vendor_ids' => []]]);
        $vendorPayload = $vendors->map(function (Vendor $vendor) {
            return [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email,
                'category' => $vendor->category,
            ];
        })->values()->all();

        return view('rfq.create', compact(
            'preparationRfq', 'budgetOptions', 'catalogRates', 'approvedActivities', 'vendors', 'selectedActivity', 'activityPayload', 'initialItems', 'vendorPayload'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rfq_id'=>['nullable','exists:rfqs,id'], 'budget_id'=>['required','exists:department_budgets,id'],
            'notes'=>['nullable','string','max:5000'], 'deadline'=>['nullable','date','after_or_equal:today'],
            'items'=>['required','array','min:1'], 'items.*.description'=>['required','string','max:500'],
            'items.*.quantity'=>['required','numeric','min:0.01'], 'items.*.unit'=>['nullable','string','max:50'],
            'items.*.request_remarks'=>['nullable','string','max:1000'], 'items.*.source_line_item_id'=>['nullable','string'],
            'items.*.vendor_ids'=>['nullable','array'], 'items.*.vendor_ids.*'=>['string'],
            'items.*.quotation_not_required'=>['nullable','boolean'], 'items.*.vendor_rate_id'=>['nullable','integer'],
        ]);
        if ($request->boolean('send_now')) \Illuminate\Support\Facades\Gate::authorize('rfq.send');
        $rfq=app(\App\Services\RfqPreparationService::class)->save($data);
        if ($request->boolean('send_now')) {
            foreach ($rfq->quotes()->where('status','invited')->get() as $quote) \App\Jobs\SendRfqEmail::dispatch($quote,$rfq);
            if ($rfq->quotes()->where('status','invited')->exists()) $rfq->update(['status'=>'sent']);
        }
        return redirect()->route('rfq.show',$rfq)->with('success','RFQ prepared. Items marked Quotation Not Required were sent to Payment Schedule.');
    }

    public function show(Rfq $rfq): View
    {
        $rfq->load(['activityForm', 'quotes.vendor', 'quotes.items', 'createdBy']);
        $additionalVendors = Vendor::active()->whereNotNull('email')->whereNotIn('id', $rfq->quotes->pluck('vendor_id'))->orderBy('name')->get();
        return view('rfq.show', compact('rfq', 'additionalVendors'));
    }

    public function edit(Rfq $rfq): View|RedirectResponse
    {
        if ($rfq->handoff_activity_id && !$rfq->requestItems()->exists() && !$rfq->quotes()->exists()) return redirect()->route('rfq.create',['rfq_id'=>$rfq->id]);
        abort_unless(in_array($rfq->status, ['draft', 'open']), 403, 'Only draft RFQs can be edited.');
        $budgets = \App\Models\DepartmentBudget::active()->orderBy('activity_title')->get();
        return view('rfq.edit', compact('rfq', 'budgets'));
    }

    public function update(Request $request, Rfq $rfq): RedirectResponse
    {
        abort_unless(in_array($rfq->status, ['draft', 'open']), 403, 'Only draft RFQs can be edited.');

        $request->validate([
            'budget_id' => ['required', 'uuid'],
            'notes'    => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
        ]);

        $budget = \App\Models\DepartmentBudget::active()->findOrFail($request->budget_id);
        abort_if($rfq->activity_form_id && $rfq->activityForm?->budget_id !== $budget->id, 422, 'Keep the budget linked to the approved activity.');
        $rfq->update([
            'budget_id' => $budget->id,
            'title'    => $budget->activity_title,
            'notes'    => $request->notes,
            'deadline' => $request->deadline,
        ]);

        AuditLog::record(Auth::user(), 'rfq.updated', $rfq, $rfq->rfq_number);

        return redirect()->route('rfq.show', $rfq)->with('success', 'RFQ updated.');
    }

    public function destroy(Rfq $rfq): RedirectResponse
    {
        abort_unless(in_array($rfq->status, ['draft', 'open', 'cancelled']), 403, 'Cannot delete a sent or closed RFQ.');

        AuditLog::record(Auth::user(), 'rfq.deleted', $rfq, $rfq->rfq_number);
        $rfq->delete();

        return redirect()->route('rfq.index')->with('success', 'RFQ deleted.');
    }

    public function send(Request $request, Rfq $rfq): RedirectResponse
    {
        abort_if(in_array($rfq->status, ['closed','cancelled','items_awarded']), 422, 'This RFQ is closed.');
        abort_unless($rfq->quotes()->where('status','invited')->exists(), 422, 'Prepare vendor invitations before sending this RFQ.');
        $rfq->load('quotes.vendor');

        foreach ($rfq->quotes()->where('status', 'invited')->get() as $quote) {
            \App\Jobs\SendRfqEmail::dispatch($quote, $rfq);
        }

        $rfq->update(['status' => 'sent']);
        AuditLog::record(Auth::user(), 'rfq.sent', $rfq, $rfq->rfq_number);

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'RFQ invitations sent to vendors.');
    }

    public function compare(Rfq $rfq): View
    {
        $rfq->load(['activityForm', 'quotes.vendor', 'quotes.items.lineItem']);
        $quotes = $rfq->quotes->whereIn('status', ['submitted', 'accepted'])->values();
        $items = collect();
        $quoteItems = [];
        $minRates = [];

        foreach ($quotes as $quote) {
            foreach ($quote->items as $item) {
                $key = $this->quoteItemKey($item);
                $items->put($key, $item);
                $quoteItems[$quote->id][$key] = $item;
                $rate = (float) $item->unit_rate;
                $minRates[$key] = isset($minRates[$key]) ? min($minRates[$key], $rate) : $rate;
            }
        }

        $minTotal = $quotes->min(fn (RfqQuote $quote) => (float) $quote->grand_total);
        $requestedKeys = $rfq->quotes->flatMap->items->map(fn($item)=>$this->quoteItemKey($item))->unique();
        $recommendedQuoteId = $quotes
            ->filter(fn($quote)=>$requestedKeys->diff($quote->items->map(fn($item)=>$this->quoteItemKey($item)))->isEmpty())
            ->sortBy(fn (RfqQuote $quote) => (float) $quote->grand_total)
            ->first()?->id;

        $normalize = fn ($value) => mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $value)));
        $catalogue = \App\Models\VendorRate::available()->with('vendor')->orderBy('unit_rate')->get();
        $approvedSuggestions = $items->map(function ($item) use ($catalogue, $normalize) {
            $name = $normalize($item->description);
            if ($name === '') return collect();
            $matches = $catalogue->filter(fn ($rate) => $normalize($rate->item_name) === $name);
            $lowest = $matches->min('unit_rate');
            return $matches->filter(fn ($rate) => (float) $rate->unit_rate === (float) $lowest);
        });

        return view('rfq.compare', compact(
            'rfq', 'quotes', 'items', 'quoteItems', 'minRates', 'minTotal', 'recommendedQuoteId', 'approvedSuggestions'
        ));
    }

    public function pdf(Rfq $rfq): Response
    {
        $rfq->load(['activityForm', 'quotes.vendor', 'quotes.items']);
        $pdf = Pdf::loadView('rfq.pdf', array_merge(compact('rfq'), DocumentBranding::data()))->setPaper('a4', 'portrait');

        return $pdf->download("RFQ-{$rfq->rfq_number}.pdf");
    }

    public function acceptQuote(Request $request, Rfq $rfq, RfqQuote $quote): RedirectResponse
    {
        abort_unless($quote->rfq_id === $rfq->id, 404);
        abort_unless($quote->status === 'submitted', 422);

        DB::transaction(function () use ($rfq, $quote) {
            $rfq->quotes()->where('id', '!=', $quote->id)->update(['status' => 'rejected']);
            $quote->update(['status' => 'accepted', 'accepted_by' => Auth::id(), 'accepted_at' => now()]);
            $rfq->update(['status' => 'closed']);
            AuditLog::record(Auth::user(), 'rfq.quote_accepted', $rfq, $rfq->rfq_number);
        });

        return redirect()->route('rfq.show', $rfq)
            ->with('success', 'Quote accepted. You can now generate a Purchase Order.');
    }

    public function acceptQuoteItem(Rfq $rfq, RfqQuote $quote, RfqQuoteItem $quoteItem): RedirectResponse
    {
        abort_unless($quote->rfq_id === $rfq->id && $quoteItem->rfq_quote_id === $quote->id, 404);
        abort_unless($quote->status === 'submitted', 422, 'Only submitted quotation items can be awarded.');
        abort_if($rfq->status === 'cancelled', 422, 'Cannot award items on a cancelled RFQ.');

        DB::transaction(function () use ($rfq, $quoteItem) {
            $items = RfqQuoteItem::query()
                ->whereHas('quote', fn ($query) => $query->where('rfq_id', $rfq->id))
                ->get();
            $targetKey = $this->quoteItemKey($quoteItem);

            foreach ($items as $item) {
                if ($this->quoteItemKey($item) !== $targetKey) {
                    continue;
                }

                $item->update($item->is($quoteItem)
                    ? ['award_status' => 'accepted', 'accepted_by' => Auth::id(), 'accepted_at' => now()]
                    : ['award_status' => 'rejected', 'accepted_by' => null, 'accepted_at' => null]);
            }

            $this->refreshItemAwardStatus($rfq);
            AuditLog::record(Auth::user(), 'rfq.item_awarded', $rfq, "{$rfq->rfq_number}: {$quoteItem->description}");
        });

        return back()->with('success', "{$quoteItem->description} has been awarded to {$quote->vendor?->name}.");
    }

    public function requestNegotiation(Request $request, Rfq $rfq, RfqQuote $quote, RfqQuoteItem $quoteItem): RedirectResponse
    {
        abort_unless($quote->rfq_id === $rfq->id && $quoteItem->rfq_quote_id === $quote->id, 404);
        abort_if($quote->entry_method === 'catalogue', 422, 'Catalogue prices are fixed. Review the approved rate in Procurement Setup.');
        abort_unless($quote->status === 'submitted', 422, 'Request a negotiation only after the vendor has submitted a quote.');
        abort_if(in_array($quoteItem->award_status, ['accepted', 'rejected'], true), 422, 'This item is no longer available for negotiation.');

        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $quoteItem->update([
            'negotiation_message' => $data['message'],
            'negotiation_requested_by' => Auth::id(),
            'negotiation_requested_at' => now(),
        ]);
        \App\Jobs\SendRfqNegotiationEmail::dispatch($quoteItem);
        AuditLog::record(Auth::user(), 'rfq.negotiation_requested', $rfq, "{$rfq->rfq_number}: {$quoteItem->description}");

        return back()->with('success', "Negotiation request queued for {$quote->vendor?->name}.");
    }

    /** Reject a single vendor's price for an item without affecting other item awards. */
    public function rejectQuoteItem(Rfq $rfq, RfqQuote $quote, RfqQuoteItem $quoteItem): RedirectResponse
    {
        abort_unless($quote->rfq_id === $rfq->id && $quoteItem->rfq_quote_id === $quote->id, 404);
        abort_unless($quote->status === 'submitted', 422, 'Only submitted quotation items can be rejected.');
        abort_if($quoteItem->award_status === 'accepted', 422, 'This item is already awarded. Award another vendor to replace it instead.');

        $quoteItem->update([
            'award_status' => 'rejected',
            'accepted_by' => null,
            'accepted_at' => null,
            'negotiation_message' => null,
            'negotiation_requested_by' => null,
            'negotiation_requested_at' => null,
        ]);
        $this->refreshItemAwardStatus($rfq);
        AuditLog::record(Auth::user(), 'rfq.item_rejected', $rfq, "{$rfq->rfq_number}: {$quoteItem->description} from {$quote->vendor?->name}");

        return back()->with('success', "{$quoteItem->description} from {$quote->vendor?->name} was rejected.");
    }

    public function inviteAdditional(Request $request, Rfq $rfq): RedirectResponse
    {
        $data=$request->validate(['vendor_ids'=>['required','array','min:1'],'vendor_ids.*'=>['required','distinct','exists:vendors,id']]);
        $quotes=DB::transaction(function() use($data,$rfq) {
            $locked=Rfq::whereKey($rfq->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($locked->status,['cancelled','closed']),422,'This RFQ is closed.');
            $vendors=Vendor::active()->whereNotNull('email')->whereIn('id',$data['vendor_ids'])->get();
            abort_unless($vendors->count()===count($data['vendor_ids']),422,'Choose active vendors with email addresses.');
            $items=$locked->quotes()->with('items')->get()->flatMap->items->unique(fn($i)=>$this->quoteItemKey($i));
            abort_if($items->isEmpty(),422,'Prepare the RFQ items first.');
            $quotes=collect();
            foreach($vendors as $vendor) {
                abort_if($locked->quotes()->where('vendor_id',$vendor->id)->exists(),422,'This vendor is already included in the RFQ.');
                $quote=$locked->quotes()->create(['vendor_id'=>$vendor->id,'entry_method'=>'portal','status'=>'invited']);
                $quote->generateToken();$quote->save();
                foreach($items as $item) $quote->items()->create(['rfq_item_id'=>$item->rfq_item_id,'line_item_id'=>$item->line_item_id,'description'=>$item->description,'quantity'=>$item->quantity,'unit'=>$item->unit,'request_remarks'=>$item->request_remarks,'unit_rate'=>0]);
                $quotes->push($quote);
            }
            AuditLog::record(Auth::user(),'rfq.additional_vendors_invited',$locked,$locked->rfq_number);
            return $quotes;
        });
        foreach($quotes as $quote) \App\Jobs\SendRfqEmail::dispatch($quote,$rfq);
        return back()->with('success','Additional quotation requests queued. Responses will appear in quotation comparison.');
    }

    public function manualQuote(Request $request, Rfq $rfq): RedirectResponse
    {
        $request->validate([
            'vendor_id'            => ['required', 'exists:vendors,id'],
            'items'                => ['nullable', 'array'],
            'items.*.description'  => ['nullable', 'string'],
            'items.*.unit_rate'    => ['required', 'numeric', 'min:0'],
            'total_amount'         => ['required', 'numeric', 'min:0'],
            'tax_amount'           => ['nullable', 'numeric', 'min:0'],
            'notes'                => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($request, $rfq) {
            $rfq->load('quotes.items');
            // Find or create quote for this vendor
            $quote = $rfq->quotes()->firstOrCreate(
                ['vendor_id' => $request->vendor_id],
                [
                    'rfq_id'       => $rfq->id,
                    'entry_method' => 'manual',
                    'status'       => 'submitted',
                    'notes'        => $request->notes,
                ]
            );

            abort_if($quote->entry_method === 'catalogue', 422, 'Approved catalogue prices cannot be overwritten by manual quotations.');
            $total = (float) ($request->total_amount ?? 0);
            if ($request->has('items')) {
                $total = 0;
                foreach ($quote->items as $qItem) {
                    if ($qItem->award_status === 'accepted') {
                        $total += (float) $qItem->total;
                        continue;
                    }
                    $rate = $request->input("items.{$qItem->id}.unit_rate", 0);
                    $qItem->update([
                        'unit_rate' => $rate,
                        'total'     => $qItem->quantity * $rate,
                    ]);
                    $total += $qItem->quantity * $rate;
                }
            }

            $taxAmount = (float) ($request->tax_amount ?? 0);
            $quote->update([
                'status' => 'submitted',
                'total_quoted' => $total,
                'tax_amount' => $taxAmount,
                'grand_total' => $total + $taxAmount,
                'notes' => $request->notes,
            ]);
            AuditLog::record(Auth::user(), 'rfq.manual_quote', $rfq, $rfq->rfq_number);
        });

        return redirect()->route('rfq.show', $rfq)->with('success', 'Manual quote saved.');
    }

    // ── Vendor Portal ──────────────────────────────────────────

    public function vendorPortal(string $token): View
    {
        $quote = RfqQuote::where('vendor_token', $token)->firstOrFail();

        if ($quote->token_used || $quote->status === 'submitted') {
            $quote->load(['rfq', 'vendor', 'items']);
            $rfq = $quote->rfq;
            return view('rfq.vendor-submitted', compact('quote', 'rfq', 'token'));
        }

        if (! $quote->isTokenValid()) {
            abort(410, 'This quotation link has expired. Please contact the purchasing team.');
        }

        $quote->load(['rfq.activityForm', 'vendor', 'items']);
        $rfq = $quote->rfq;
        return view('rfq.vendor-portal', compact('quote', 'rfq', 'token'));
    }

    public function vendorSubmit(Request $request, string $token): RedirectResponse
    {
        $quote = RfqQuote::where('vendor_token', $token)->firstOrFail();

        if (! $quote->isTokenValid()) {
            abort(410, 'This quotation link has expired or already been used.');
        }

        $request->validate([
            'items'             => ['required', 'array'],
            'items.*.unit_rate' => ['required', 'numeric', 'min:0'],
            'tax_applied'       => ['nullable', 'boolean'],
            'tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delivery_timeline' => ['nullable', 'string', 'max:255'],
            'payment_terms'     => ['nullable', 'string', 'max:2000'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $quote) {
            $total = 0;
            foreach ($quote->items as $qItem) {
                if ($qItem->award_status === 'accepted') {
                    $total += (float) $qItem->total;
                    continue;
                }
                $rate = $request->items[$qItem->id]['unit_rate'] ?? 0;
                $qItem->update(['unit_rate' => $rate, 'total' => $qItem->quantity * $rate]);
                $total += $qItem->quantity * $rate;
            }
            $taxApplied = $request->boolean('tax_applied');
            $taxRate = $taxApplied ? (float) ($request->tax_rate ?? 13) : 0;
            $taxAmount = round($total * $taxRate / 100, 2);
            $quote->update([
                'status'       => 'submitted',
                'total_quoted' => $total,
                'tax_amount'   => $taxAmount,
                'tax_applied'  => $taxApplied,
                'tax_rate'     => $taxRate,
                'grand_total'  => $total + $taxAmount,
                'notes'        => $request->notes,
                'delivery_timeline' => $request->delivery_timeline,
                'payment_terms' => $request->payment_terms,
                'quote_date'   => now()->toDateString(),
                'submitted_at' => now(),
                'token_used'   => true,
            ]);
            $quote->rfq->update(['status' => 'quotes_received']);
            AuditLog::record(null, 'rfq.vendor_submitted', $quote->rfq, $quote->rfq->rfq_number);
        });

        return redirect()->route('rfq.vendor.portal', $token)
            ->with('success', 'Your quotation has been submitted. Thank you!');
    }

    private function nextRfqNumber(): string
    {
        $year  = now()->format('Y');
        $count = Rfq::withoutGlobalScope('fiscal_year')->withTrashed()->whereYear('created_at', $year)->count() + 1;
        return sprintf('RFQ-%s-%04d', $year, $count);
    }

    private function quoteItemKey(RfqQuoteItem $item): string
    {
        if ($item->rfq_item_id) return 'rfq-item:'.$item->rfq_item_id;
        return $item->line_item_id
            ? "line-item:{$item->line_item_id}"
            : 'manual:' . md5("{$item->description}|{$item->quantity}|{$item->unit}");
    }

    private function refreshItemAwardStatus(Rfq $rfq): void
    {
        $items = RfqQuoteItem::query()
            ->whereHas('quote', fn ($query) => $query->where('rfq_id', $rfq->id))
            ->get();
        $requested = $items->map(fn (RfqQuoteItem $item) => $this->quoteItemKey($item))->unique();
        $awarded = $items->where('award_status', 'accepted')
            ->map(fn (RfqQuoteItem $item) => $this->quoteItemKey($item))
            ->unique();

        $rfq->update(['status' => $awarded->isEmpty()
            ? 'quotes_received'
            : ($requested->isNotEmpty() && $requested->count() === $awarded->count()
                ? 'items_awarded'
                : 'partially_awarded')]);
    }
}
