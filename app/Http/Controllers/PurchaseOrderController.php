<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ApprovalChain;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\RfqQuote;
use App\Models\Setting;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Support\DocumentBranding;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Services\ApprovalService;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly ApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        $query = PurchaseOrder::with(['vendor', 'rfqQuote.rfq', 'generatedBy', 'approvalChain'])->latest();
        \App\Support\RecordVisibility::apply($query, Auth::user());

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('po_number', 'like', "%{$request->search}%");
            });
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(20);
        return view('purchase-orders.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        $vendors     = Vendor::where('is_active', true)->orderBy('name')->get();
        $awardedQuotes = RfqQuote::query()
            ->whereHas('items', function ($query) {
                $query->where('award_status', 'accepted')->whereDoesntHave('purchaseOrderItem');
            })
            ->with([
                'rfq', 'vendor',
                'items' => function ($query) {
                    $query->where('award_status', 'accepted')->whereDoesntHave('purchaseOrderItem');
                },
            ])
            ->get();

        $selectedQuote = $awardedQuotes->firstWhere('id', $request->rfq_quote_id);
        $purchaseOrderChain = $this->configuredPurchaseOrderChain();

        $approvedRates = \App\Models\VendorRate::available()->with('vendor')->orderBy('unit_rate')->get()
            ->map(fn ($rate) => [
                'id' => $rate->id, 'item_name' => $rate->item_name, 'unit' => $rate->unit,
                'unit_rate' => $rate->unit_rate, 'vendor_name' => $rate->vendor->name,
                'specification' => $rate->specification,
                'valid_until' => $rate->valid_until->format('d M Y'),
            ]);
        $budgetOptions = $this->budgetOptions();
        return view('purchase-orders.create', compact('vendors', 'awardedQuotes', 'selectedQuote', 'purchaseOrderChain', 'approvedRates', 'budgetOptions'));
    }

    /** Active department budgets with live reserved/remaining figures, for the standalone-PO budget picker. */
    private function budgetOptions(?PurchaseOrder $excluding = null): \Illuminate\Support\Collection
    {
        return \App\Models\DepartmentBudget::active()->with('department')->orderBy('activity_title')->get()
            ->map(fn (\App\Models\DepartmentBudget $budget) => [
                'id' => $budget->id,
                'title' => $budget->activity_title,
                'department_name' => $budget->department?->name,
                'fiscal_year' => $budget->fiscal_year,
                'allocated' => (float) $budget->allocated_amount,
                'reserved' => $budget->reservedAmount(null, $excluding?->id),
                'remaining' => $budget->remainingAmount(null, $excluding?->id),
            ])->values();
    }

    public function store(Request $request): RedirectResponse
    {
        // An RFQ-based PO is always vendor-specific and uses only awarded items.
        $sourceQuote = $request->rfq_quote_id ? RfqQuote::find($request->rfq_quote_id) : null;
        if ($sourceQuote) {
            $request->merge(['vendor_id' => $sourceQuote->vendor_id]);
        }

        $request->validate([
            'vendor_id'           => ['required', 'exists:vendors,id'],
            'rfq_quote_id'        => ['nullable', 'exists:rfq_quotes,id'],
            'title'               => ['required_without:rfq_quote_id', 'nullable', 'string', 'max:255'],
            'budget_id'           => ['required_without:rfq_quote_id', 'nullable', 'exists:department_budgets,id'],
            'delivery_address'    => ['required', 'string', 'max:2000'],
            'expected_delivery_date' => ['required', 'date', 'after_or_equal:today'],
            'terms_and_conditions'   => ['required', 'string', 'max:5000'],
            'tax_applied'         => ['nullable', 'boolean'],
            'tax_rate'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            // Manual line items
            'items'               => ['required_without:rfq_quote_id', 'array', 'min:1'],
            'items.*.description' => ['required_with:items', 'string', 'max:500'],
            'items.*.quantity'    => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit'        => ['nullable', 'string', 'max:50'],
            'items.*.unit_rate'   => ['required_with:items', 'numeric', 'min:0'],
        ]);

        $po = DB::transaction(function () use ($request) {
            $quote = $request->rfq_quote_id
                ? RfqQuote::with(['vendor', 'items' => function ($query) {
                    $query->where('award_status', 'accepted')->whereDoesntHave('purchaseOrderItem');
                }])->findOrFail($request->rfq_quote_id)
                : null;
            if ($quote) {
                abort_if($quote->items->isEmpty(), 422, 'There are no unprocessed awarded items available for this vendor.');
            }

            $subtotal   = 0;
            $taxApplied = $request->boolean('tax_applied');
            $taxRate = $taxApplied ? (float) ($request->tax_rate ?? 13) : 0;

            $po = PurchaseOrder::create([
                'po_number'              => $this->nextPoNumber(),
                'title'                  => $quote ? null : $request->title,
                'budget_id'              => $quote ? null : $request->budget_id,
                'vendor_id'              => $request->vendor_id,
                'purchase_request_id'    => null,
                'rfq_quote_id'           => $quote?->id,
                'generated_by'           => Auth::id(),
                // The PO chain is never accepted from the creator's request.
                // It is assigned from the administrator-controlled setting at submission.
                'approval_chain_id'      => null,
                'delivery_address'       => $request->delivery_address,
                'expected_delivery_date' => $request->expected_delivery_date,
                'terms_and_conditions'   => $request->terms_and_conditions,
                'subtotal'               => $subtotal,
                'tax_applied'            => $taxApplied,
                'tax_rate'               => $taxRate,
                'tax_amount'             => 0,
                'total_amount'           => 0,
                'status'                 => 'generated',
            ]);

            if ($quote) {
                foreach ($quote->items as $quoteItem) {
                    $total = (float) $quoteItem->total;
                    $subtotal += $total;
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'rfq_quote_item_id' => $quoteItem->id,
                        'description'       => $quoteItem->description,
                        'quantity'          => $quoteItem->quantity,
                        'unit'              => $quoteItem->unit,
                        'request_remarks'   => $quoteItem->request_remarks,
                        'unit_rate'         => $quoteItem->unit_rate,
                        'total'             => $total,
                    ]);
                }
            } elseif ($request->has('items')) {
                foreach ($request->items as $item) {
                    if (empty($item['description'])) continue;
                    $qty   = (float) ($item['quantity'] ?? 1);
                    $rate  = (float) ($item['unit_rate'] ?? 0);
                    $total = $qty * $rate;
                    $subtotal += $total;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'description'       => $item['description'],
                        'quantity'          => $qty,
                        'unit'              => $item['unit'] ?? null,
                        'unit_rate'         => $rate,
                        'total'             => $total,
                    ]);
                }
            }

            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $po->update(['subtotal' => $subtotal, 'tax_amount' => $taxAmount, 'total_amount' => $subtotal + $taxAmount]);

            AuditLog::record(Auth::user(), 'purchase_order.created', $po, $po->po_number);
            return $po;
        });

        if ($request->boolean('submit_for_approval')) {
            $chain = $this->configuredPurchaseOrderChain();
            if (! $chain) {
                return redirect()->route('purchase-orders.show', $po)
                    ->withErrors(['approval_chain' => 'PO saved as generated. An administrator must configure an active Purchase Order approval chain in Administration → Approval Chains before it can be submitted.']);
            }
            $po->update(['approval_chain_id' => $chain->id]);
            $this->approvalService->submit($po, Auth::user());
            return redirect()->route('purchase-orders.show', $po)
                ->with('success', "Purchase Order {$po->po_number} submitted for verification.");
        }

        return redirect()->route('purchase-orders.show', $po)
            ->with('success', "Purchase Order {$po->po_number} created.");
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('view', $purchaseOrder);
        $purchaseOrder->load([
            'vendor', 'purchaseRequest.lineItems', 'rfqQuote.rfq', 'generatedBy', 'budget.department',
            'approvalChain.verifiers', 'approvalChain.approvers', 'verifier', 'approver', 'approvalActions.actor',
            'items.rfqQuoteItem', 'goodsReceived', 'payments', 'vendorBills.vendor', 'procurementChecklists.vendorBill',
        ]);

        return view('purchase-orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorize('update', $purchaseOrder);
        abort_unless($purchaseOrder->isEditable(), 403, 'Only generated or returned POs can be edited.');
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $purchaseOrder->load(['items', 'vendor']);
        $budgetOptions = $this->budgetOptions($purchaseOrder);
        return view('purchase-orders.edit', compact('purchaseOrder', 'vendors', 'budgetOptions'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);
        abort_unless($purchaseOrder->isEditable(), 403, 'Only generated or returned POs can be edited.');

        $request->validate([
            'title'                  => [\Illuminate\Validation\Rule::requiredIf(! $purchaseOrder->rfq_quote_id), 'nullable', 'string', 'max:255'],
            'budget_id'              => [\Illuminate\Validation\Rule::requiredIf(! $purchaseOrder->rfq_quote_id), 'nullable', 'exists:department_budgets,id'],
            'delivery_address'       => ['required', 'string', 'max:2000'],
            'expected_delivery_date' => ['required', 'date'],
            'terms_and_conditions'   => ['required', 'string', 'max:5000'],
            'tax_applied'            => ['nullable', 'boolean'],
            'tax_rate'               => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items'                  => ['nullable', 'array'],
            'items.*.description'    => ['required_with:items', 'string', 'max:500'],
            'items.*.quantity'       => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.unit'           => ['nullable', 'string', 'max:50'],
            'items.*.unit_rate'      => ['required_with:items', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            $taxApplied = $request->boolean('tax_applied');
            $taxRate = $taxApplied ? (float) ($request->tax_rate ?? 13) : 0;

            $purchaseOrder->update([
                'delivery_address'       => $request->delivery_address,
                'expected_delivery_date' => $request->expected_delivery_date,
                'terms_and_conditions'   => $request->terms_and_conditions,
                'tax_applied'            => $taxApplied,
                'tax_rate'               => $taxRate,
                ...(! $purchaseOrder->rfq_quote_id ? [
                    'title'     => $request->title,
                    'budget_id' => $request->budget_id,
                ] : []),
            ]);

            // If standalone PO (no RFQ quote), allow editing items
            if (! $purchaseOrder->rfq_quote_id && $request->has('items')) {
                $purchaseOrder->items()->delete();
                $subtotal = 0;
                foreach ($request->items as $item) {
                    if (empty($item['description'])) continue;
                    $qty   = (float) ($item['quantity'] ?? 1);
                    $rate  = (float) ($item['unit_rate'] ?? 0);
                    $total = $qty * $rate;
                    $subtotal += $total;
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'description'       => $item['description'],
                        'quantity'          => $qty,
                        'unit'              => $item['unit'] ?? null,
                        'unit_rate'         => $rate,
                        'total'             => $total,
                    ]);
                }
                $purchaseOrder->update(['subtotal' => $subtotal]);
            }
            $subtotal = (float) $purchaseOrder->subtotal;
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $purchaseOrder->update(['tax_amount' => $taxAmount, 'total_amount' => $subtotal + $taxAmount]);

            AuditLog::record(Auth::user(), 'purchase_order.updated', $purchaseOrder, $purchaseOrder->po_number);
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('success', 'Purchase Order updated.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('delete', $purchaseOrder);
        abort_unless($purchaseOrder->isEditable(), 403, 'Cannot delete a submitted, approved, sent, or received PO.');
        AuditLog::record(Auth::user(), 'purchase_order.deleted', $purchaseOrder, $purchaseOrder->po_number);
        $purchaseOrder->delete();
        return redirect()->route('purchase-orders.index')->with('success', 'Purchase Order deleted.');
    }

    public function pdf(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['vendor', 'rfqQuote.rfq', 'items.rfqQuoteItem', 'generatedBy']);
        $po = $purchaseOrder;
        $pdf = Pdf::loadView('purchase-orders.pdf', array_merge(compact('po'), DocumentBranding::data()))->setPaper('a4', 'portrait');
        return $pdf->download("PO-{$purchaseOrder->po_number}.pdf");
    }

    public function downloadVendorBill(PurchaseOrder $purchaseOrder, VendorBill $vendorBill): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk('private')->download(
            ...$this->resolveVendorBillFile($purchaseOrder, $vendorBill),
        );
    }

    public function viewVendorBill(PurchaseOrder $purchaseOrder, VendorBill $vendorBill): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return Storage::disk('private')->response(
            ...$this->resolveVendorBillFile($purchaseOrder, $vendorBill),
        );
    }

    /** Shared guard for the vendor-bill view/download endpoints: [disk_path, name, headers]. */
    private function resolveVendorBillFile(PurchaseOrder $purchaseOrder, VendorBill $vendorBill): array
    {
        // Anyone who can work a checklist can pull the bill it's attached to,
        // regardless of whether they created/chain-approved the underlying PO.
        if (! Auth::user()->can('checklists.view')) {
            $this->authorize('view', $purchaseOrder);
        }
        abort_unless($vendorBill->purchase_order_id === $purchaseOrder->id, 404);
        abort_unless(Storage::disk('private')->exists($vendorBill->disk_path), 404);

        return [$vendorBill->disk_path, $vendorBill->original_name, ['Content-Type' => $vendorBill->mime_type]];
    }

    public function sendToVendor(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->status === 'approved', 422, 'Only an approved Purchase Order can be sent to the vendor.');
        $this->publishToVendor($purchaseOrder);
        return back()->with('success', 'Purchase Order emailed to vendor.');
    }

    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('update', $purchaseOrder);
        abort_unless($purchaseOrder->isEditable(), 422, 'Only a generated or returned Purchase Order can be submitted.');

        $chain = $this->configuredPurchaseOrderChain();
        abort_unless($chain, 422, 'An administrator must configure an active Purchase Order approval chain in Administration → Approval Chains before submitting this PO.');
        $purchaseOrder->update(['approval_chain_id' => $chain->id]);

        $this->approvalService->submit($purchaseOrder, Auth::user());

        return back()->with('success', 'Purchase Order submitted for verification.');
    }

    public function verify(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('verify', $purchaseOrder);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,modified_approved,rejected'],
            'note' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected'],
        ]);
        $this->approvalService->verify($purchaseOrder, Auth::user(), $data['decision'], $data['note'] ?? null);

        return back()->with('success', 'Verification action recorded.');
    }

    public function approve(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize('finalApprove', $purchaseOrder);
        $data = $request->validate([
            'decision' => ['required', 'in:approved,modified_approved,rejected'],
            'note' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected'],
        ]);
        $this->approvalService->approve($purchaseOrder, Auth::user(), $data['decision'], $data['note'] ?? null);
        if ($data['decision'] === 'approved' && $purchaseOrder->fresh()->isApproved()) {
            $this->publishToVendor($purchaseOrder);
            return back()->with('success', 'Purchase Order approved and issued to the vendor portal.');
        }

        return back()->with('success', 'Approval action recorded.');
    }

    private function nextPoNumber(): string
    {
        $year  = now()->format('Y');
        $count = PurchaseOrder::withoutGlobalScope('fiscal_year')->withTrashed()->whereYear('created_at', $year)->count() + 1;
        return sprintf('PO-%s-%04d', $year, $count);
    }

    private function configuredPurchaseOrderChain(): ?ApprovalChain
    {
        $chainId = Setting::get('purchase_order_approval_chain_id');
        return $chainId ? ApprovalChain::active()->find($chainId) : null;
    }

    /** Publish only after final approval: portal visibility and email always move together. */
    private function publishToVendor(PurchaseOrder $purchaseOrder): void
    {
        $purchaseOrder->loadMissing('vendor');
        if ($purchaseOrder->status !== 'approved') return;

        $purchaseOrder->update([
            'status' => 'sent_to_vendor',
            'sent_to_vendor_at' => now(),
        ]);
        \App\Jobs\SendPurchaseOrderEmail::dispatch($purchaseOrder);
        AuditLog::record(Auth::user(), 'purchase_order.issued_vendor', $purchaseOrder, $purchaseOrder->po_number);
    }
}
