<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PurchaseOrder;
use App\Models\ProcurementChecklist;
use App\Models\RfqQuote;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\UploadOptimizationService;
use App\Support\DocumentBranding;
use App\Support\VendorStatement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VendorPortalController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly UploadOptimizationService $uploadOptimizer,
    ) {}

    public function login(): View
    {
        return view('vendor-portal.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $vendor = Vendor::where('email', strtolower($credentials['email']))
            ->where('is_active', true)
            ->where('portal_enabled', true)
            ->first();

        if (! $vendor || ! $vendor->portal_password || ! Hash::check($credentials['password'], $vendor->portal_password)) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'The provided vendor portal credentials are incorrect.']);
        }

        $request->session()->regenerate();
        $request->session()->put('vendor_portal_id', $vendor->id);
        $vendor->update(['portal_last_login_at' => now()]);

        return redirect()->route('vendor.portal.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('vendor_portal_id');
        $request->session()->regenerateToken();
        return redirect()->route('vendor.portal.login');
    }

    public function dashboard(Request $request): View
    {
        $vendor = $request->attributes->get('portal_vendor');
        $quotes = $vendor->rfqQuotes()
            ->with(['rfq', 'items'])
            ->whereIn('status', ['invited', 'submitted', 'accepted', 'rejected'])
            ->latest()
            ->get();

        $negotiationRequests = $quotes->filter(function (RfqQuote $quote) {
            return $quote->items->contains(fn ($item) => $item->negotiation_message && !in_array($item->award_status, ['accepted', 'rejected'], true));
        })->values();
        $actionRequired = $quotes->where('status', 'invited')
            ->merge($negotiationRequests)
            ->unique('id')
            ->values();
        $awardedQuotes = $quotes->filter(fn (RfqQuote $quote) => $quote->items->contains('award_status', 'accepted'))->values();
        $underReviewQuotes = $quotes->filter(function (RfqQuote $quote) {
            return $quote->status === 'submitted'
                && ! $quote->items->contains('award_status', 'accepted')
                && ! ($quote->items->isNotEmpty() && $quote->items->every(fn ($item) => $item->award_status === 'rejected'));
        })->values();
        $filter = $request->string('filter')->toString() ?: 'all';
        $displayQuotes = match ($filter) {
            'action' => $actionRequired,
            'negotiation' => $negotiationRequests,
            'submitted' => $underReviewQuotes,
            'awarded' => $awardedQuotes,
            default => $quotes,
        };

        $summary = [
            'action_required' => $actionRequired->count(),
            'negotiation' => $negotiationRequests->count(),
            'submitted' => $underReviewQuotes->count(),
            'awarded' => $quotes->pluck('items')->flatten()->where('award_status', 'accepted')->count(),
        ];

        // A supplier only sees POs that have actually been issued to it.
        $orders = $vendor->purchaseOrders()
            ->with(['items.vendorBillItems', 'rfqQuote.rfq'])
            ->whereIn('status', ['sent_to_vendor', 'goods_pending', 'partially_received', 'fully_received'])
            ->latest()
            ->get();

        // A PO is complete only after every awarded quantity has been invoiced.
        $poFilter = $request->string('po_filter')->toString() ?: 'all';
        $pendingOrders = $orders->filter(fn (PurchaseOrder $order) => ! $order->allItemsInvoiced())->values();
        $completedOrders = $orders->filter(fn (PurchaseOrder $order) => $order->allItemsInvoiced())->values();
        $displayOrders = match ($poFilter) {
            'pending' => $pendingOrders,
            'completed' => $completedOrders,
            default => $orders,
        };
        $poSummary = [
            'pending' => $pendingOrders->count(),
            'completed' => $completedOrders->count(),
        ];
        $payments = $vendor->payments()
            ->whereIn('status', ['authorised', 'scheduled', 'paid', 'processing'])
            ->where(fn ($query) => $query->whereNotNull('parent_payment_id')->orWhereNotIn('source', ['checklist', 'external', 'imported_vendor', 'direct_activity_form']))
            ->with('purchaseOrder')
            ->latest()
            ->get();

        return view('vendor-portal.dashboard', compact(
            'vendor', 'quotes', 'summary', 'actionRequired', 'negotiationRequests', 'displayQuotes', 'filter',
            'orders', 'pendingOrders', 'completedOrders', 'displayOrders', 'poFilter', 'poSummary', 'payments'
        ));
    }

    public function orders(Request $request): View
    {
        $vendor = $request->attributes->get('portal_vendor');
        $orders = $vendor->purchaseOrders()->with(['items.vendorBillItems', 'rfqQuote.rfq'])
            ->whereIn('status', ['sent_to_vendor', 'goods_pending', 'partially_received', 'fully_received'])->latest()->get();
        $filter = $request->string('filter')->toString() ?: 'all';
        $displayOrders = match ($filter) {
            'open' => $orders->filter(fn (PurchaseOrder $order) => ! $order->allItemsInvoiced())->values(),
            'completed' => $orders->filter(fn (PurchaseOrder $order) => $order->allItemsInvoiced())->values(),
            default => $orders,
        };
        return view('vendor-portal.orders', compact('vendor', 'orders', 'displayOrders', 'filter'));
    }

    public function quotes(Request $request): View
    {
        $vendor = $request->attributes->get('portal_vendor');
        $quotes = $vendor->rfqQuotes()->with(['rfq', 'items'])->whereIn('status', ['invited', 'submitted', 'accepted', 'rejected'])->latest()->get();
        $filter = $request->string('filter')->toString() ?: 'all';
        $displayQuotes = $quotes->filter(function (RfqQuote $quote) use ($filter) {
            $negotiation = $quote->items->contains(fn ($item) => $item->negotiation_message && !in_array($item->award_status, ['accepted', 'rejected'], true));
            return match ($filter) {
                'action' => $quote->status === 'invited' || $negotiation,
                'review' => $quote->status === 'submitted' && ! $negotiation,
                'awarded' => $quote->items->contains('award_status', 'accepted'),
                default => true,
            };
        })->values();
        return view('vendor-portal.quotes', compact('vendor', 'quotes', 'displayQuotes', 'filter'));
    }

    public function payments(Request $request): View
    {
        $vendor = $request->attributes->get('portal_vendor');
        $payments = $vendor->payments()->with('purchaseOrder')
            ->whereIn('status', ['scheduled', 'in_authorisation', 'authorised', 'paid', 'processing'])
            ->where(fn ($query) => $query->whereNotNull('parent_payment_id')->orWhereNotIn('source', ['checklist', 'external', 'imported_vendor', 'direct_activity_form']))
            ->latest()->get();
        return view('vendor-portal.payments', compact('vendor', 'payments'));
    }

    public function statement(Request $request): View
    {
        $vendor = $request->attributes->get('portal_vendor');
        return view('vendor-portal.statement', array_merge(compact('vendor'), VendorStatement::for($vendor)));
    }

    public function quote(Request $request, RfqQuote $quote): View
    {
        $vendor = $request->attributes->get('portal_vendor');
        abort_unless($quote->vendor_id === $vendor->id, 404);

        $quote->load(['rfq', 'vendor', 'items']);
        $rfq = $quote->rfq;
        $allowEdit = $quote->status === 'invited' || $this->hasActiveNegotiation($quote);
        $submitUrl = route('vendor.portal.quotes.submit', $quote);

        return view('rfq.vendor-portal', compact('quote', 'rfq', 'allowEdit', 'submitUrl'));
    }

    public function submitQuote(Request $request, RfqQuote $quote): RedirectResponse
    {
        $vendor = $request->attributes->get('portal_vendor');
        abort_unless($quote->vendor_id === $vendor->id, 404);
        if ($quote->status !== 'invited' && ! $this->hasActiveNegotiation($quote)) {
            return redirect()->route('vendor.portal.quotes.show', $quote)
                ->withErrors(['quote' => 'This quotation is no longer open for changes.']);
        }

        $this->saveQuote($request, $quote);
        return redirect()->route('vendor.portal.dashboard')->with('success', 'Your quotation has been submitted.');
    }

    public function purchaseOrder(Request $request, PurchaseOrder $purchaseOrder): View
    {
        $vendor = $request->attributes->get('portal_vendor');
        abort_unless($purchaseOrder->vendor_id === $vendor->id, 404);
        abort_unless(in_array($purchaseOrder->status, ['sent_to_vendor', 'goods_pending', 'partially_received', 'fully_received']), 404);

        $purchaseOrder->load([
            'vendor',
            'items.vendorBillItems',
            'rfqQuote.rfq',
            // The submitted timestamp belongs to the bill, not to the
            // purchase-order item nested beneath it.
            'vendorBills' => fn ($query) => $query->latest('submitted_at'),
            'vendorBills.items.purchaseOrderItem',
        ]);

        return view('vendor-portal.purchase-order', compact('vendor', 'purchaseOrder'));
    }

    public function purchaseOrderPdf(Request $request, PurchaseOrder $purchaseOrder): \Symfony\Component\HttpFoundation\Response
    {
        $vendor = $request->attributes->get('portal_vendor');
        abort_unless($purchaseOrder->vendor_id === $vendor->id, 404);
        abort_unless(in_array($purchaseOrder->status, ['sent_to_vendor', 'goods_pending', 'partially_received', 'fully_received']), 404);

        $purchaseOrder->load(['vendor', 'items', 'rfqQuote.rfq']);
        $po = $purchaseOrder;

        return Pdf::loadView('purchase-orders.pdf', array_merge(compact('po'), DocumentBranding::data()))
            ->setPaper('a4', 'portrait')
            ->download("PO-{$po->po_number}.pdf");
    }

    public function changePasswordForm(Request $request): View
    {
        return view('vendor-portal.change-password', ['vendor' => $request->attributes->get('portal_vendor')]);
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $vendor = $request->attributes->get('portal_vendor');
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $vendor->portal_password)) {
            return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
        }

        $vendor->update([
            'portal_password'            => Hash::make($data['password']),
            'portal_password_changed_at' => now(),
        ]);

        return redirect()->route('vendor.portal.dashboard')->with('success', 'Your password has been changed.');
    }

    public function profile(Request $request): View
    {
        return view('vendor-portal.profile', ['vendor' => $request->attributes->get('portal_vendor')]);
    }

    /** Update only vendor-owned operational details. Login email remains staff-controlled. */
    public function updateProfile(Request $request): RedirectResponse
    {
        $vendor = $request->attributes->get('portal_vendor');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::in(Vendor::categories())],
            'company_type' => ['nullable', 'string', Rule::in(Vendor::companyTypes())],
            'pan_vat_number' => ['nullable', 'string', 'max:100'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:50'],
            'office_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // A blank bank number means keep the encrypted value already on file.
        if (blank($data['bank_account_number'] ?? null)) {
            unset($data['bank_account_number']);
        }

        $before = $vendor->only(['name', 'contact_person', 'mobile_number', 'office_number', 'address']);
        $vendor->update($data);
        AuditLog::record(null, 'vendor.portal_profile_updated', $vendor, $vendor->name, $before, $vendor->only(array_keys($before)));

        return redirect()->route('vendor.portal.profile.edit')->with('success', 'Your vendor information has been updated. Your login email remains managed by Kathford administration.');
    }

    public function submitBill(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $vendor = $request->attributes->get('portal_vendor');
        $this->ensureVendorCanAccessOrder($vendor, $purchaseOrder);

        $data = $request->validate([
            'bill_number' => ['required', 'string', 'max:100'],
            'bill_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'bill_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,heic,heif', 'max:20480'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $duplicate = $purchaseOrder->vendorBills()->where('bill_number', $data['bill_number'])->exists();
        if ($duplicate) {
            return back()->withInput()->withErrors(['bill_number' => 'This bill number has already been submitted for this purchase order.']);
        }

        $purchaseOrder->load('items.vendorBillItems');
        $invoiceLines = [];
        $subtotal = 0.0;
        foreach ($purchaseOrder->items as $item) {
            $quantity = (float) data_get($data, "items.{$item->id}.quantity", 0);
            if ($quantity <= 0) continue;
            $remaining = max(0, (float) $item->quantity - $item->invoicedQuantity());
            if ($quantity - $remaining > 0.0001) {
                return back()->withInput()->withErrors(["items.{$item->id}.quantity" => "{$item->description}: the invoiced quantity cannot exceed the remaining {$remaining} {$item->unit}."]);
            }
            $lineTotal = round($quantity * (float) $item->unit_rate, 2);
            $invoiceLines[] = compact('item', 'quantity', 'lineTotal');
            $subtotal += $lineTotal;
        }
        if ($invoiceLines === []) return back()->withInput()->withErrors(['items' => 'Enter an invoice quantity for at least one remaining PO item.']);

        $tax = $purchaseOrder->tax_applied ? round($subtotal * (float) $purchaseOrder->tax_rate / 100, 2) : 0;
        $file = $request->file('bill_file');
        $stored = $this->uploadOptimizer->store($file, "vendor-bills/{$purchaseOrder->id}");
        $bill = DB::transaction(function () use ($purchaseOrder, $vendor, $data, $stored, $subtotal, $tax, $invoiceLines) {
            $bill = VendorBill::create([
            'purchase_order_id' => $purchaseOrder->id,
            'vendor_id' => $vendor->id,
            'bill_number' => $data['bill_number'],
            'bill_date' => $data['bill_date'],
            'amount' => $subtotal + $tax,
            'tax_amount' => $tax,
            'notes' => $data['notes'] ?? null,
            'disk_path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'mime_type' => $stored['mime_type'],
            'file_size' => $stored['file_size'],
            'status' => 'submitted',
            'submitted_at' => now(),
            ]);
            foreach ($invoiceLines as $line) {
                $bill->items()->create([
                    'purchase_order_item_id' => $line['item']->id,
                    'quantity' => $line['quantity'],
                    'unit_rate' => $line['item']->unit_rate,
                    'total' => $line['lineTotal'],
                ]);
            }

            $checklist = ProcurementChecklist::create([
            'vendor_bill_id' => $bill->id,
            'purchase_order_id' => $purchaseOrder->id,
            'vendor_id' => $vendor->id,
            'fulfillment_type' => $vendor->category === 'Services' ? 'service' : 'goods',
            'status' => 'pending_controls',
            ]);
            $purchaseOrder->load('items.vendorBillItems');
            $purchaseOrder->update(['status' => $purchaseOrder->allItemsInvoiced() ? 'fully_received' : 'partially_received']);
            return [$bill, $checklist];
        });

        [$bill, $checklist] = $bill;

        AuditLog::record(null, 'vendor.bill_submitted', $purchaseOrder, "{$purchaseOrder->po_number} / {$bill->bill_number}");
        $recipients = User::active()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['finance', 'super_admin']))
            ->get();
        $this->notifications->sendToMany(
            $recipients->all(),
            'vendor_bill_submitted',
            'Vendor bill submitted',
            "{$vendor->name} submitted bill {$bill->bill_number} for {$purchaseOrder->po_number}. A control checklist is ready for review.",
            route('checklists.show', $checklist),
        );

        return redirect()->route('vendor.portal.orders.show', $purchaseOrder)->with('success', 'Your bill was submitted and Kathford has been notified.');
    }

    private function ensureVendorCanAccessOrder(Vendor $vendor, PurchaseOrder $purchaseOrder): void
    {
        abort_unless($purchaseOrder->vendor_id === $vendor->id, 404);
        abort_unless(in_array($purchaseOrder->status, ['sent_to_vendor', 'goods_pending', 'partially_received', 'fully_received'], true), 404);
    }

    private function hasActiveNegotiation(RfqQuote $quote): bool
    {
        return $quote->items()
            ->whereNotNull('negotiation_message')
            ->where(function ($query) {
                $query->whereNull('award_status')
                    ->orWhereNotIn('award_status', ['accepted', 'rejected']);
            })
            ->exists();
    }

    private function saveQuote(Request $request, RfqQuote $quote): void
    {
        $data = $request->validate([
            'items'             => ['required', 'array'],
            'items.*.unit_rate' => ['required', 'numeric', 'min:0'],
            'tax_applied'       => ['nullable', 'boolean'],
            'tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'delivery_timeline' => ['nullable', 'string', 'max:255'],
            'payment_terms'     => ['nullable', 'string', 'max:2000'],
            'notes'             => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $quote): void {
            $quote->load('items');
            $total = 0;
            foreach ($quote->items as $item) {
                if (in_array($item->award_status, ['accepted', 'rejected'], true)) {
                    $total += (float) $item->total;
                    continue;
                }
                $rate = (float) ($data['items'][$item->id]['unit_rate'] ?? 0);
                $item->update([
                    'unit_rate' => $rate,
                    'total' => $item->quantity * $rate,
                    'negotiation_message' => null,
                    'negotiation_requested_by' => null,
                    'negotiation_requested_at' => null,
                ]);
                $total += $item->quantity * $rate;
            }
            $taxApplied = (bool) ($data['tax_applied'] ?? false);
            $taxRate = $taxApplied ? (float) ($data['tax_rate'] ?? 13) : 0;
            $taxAmount = round($total * $taxRate / 100, 2);
            $quote->update([
                'status'            => 'submitted',
                'total_quoted'      => $total,
                'tax_amount'        => $taxAmount,
                'tax_applied'       => $taxApplied,
                'tax_rate'          => $taxRate,
                'grand_total'       => $total + $taxAmount,
                'notes'             => $data['notes'] ?? null,
                'delivery_timeline' => $data['delivery_timeline'] ?? null,
                'payment_terms'     => $data['payment_terms'] ?? null,
                'quote_date'        => now()->toDateString(),
                'submitted_at'      => now(),
            ]);
            $quote->rfq->update(['status' => 'quotes_received']);
            AuditLog::record(null, 'rfq.vendor_portal_submitted', $quote->rfq, $quote->rfq->rfq_number);
        });
    }
}
