<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Payee;
use App\Models\PaymentAccount;
use App\Models\PaymentAuthorisationChannel;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Jobs\SendVendorPaymentScheduledEmail;
use App\Jobs\SendVendorPaymentPaidEmail;
use App\Support\PaymentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PaymentController extends Controller
{
    private function authorizeFinance(): void
    {
        abort_unless(
            auth()->user()->isSuperAdmin()
                || auth()->user()->permissions()->where('module', 'payments')->exists(),
            403,
            'You do not have payment-schedule access.'
        );
    }

    /**
     * Invoice imports create finance records in bulk, so access is deliberately
     * separate from ordinary payment scheduling. Super Admin retains the Gate
     * bypass configured in AppServiceProvider.
     */
    private function authorizeVendorInvoiceImport(): void
    {
        abort_unless(
            auth()->user()->can('payments.import_vendor_invoices'),
            403,
            'You do not have permission to import pending vendor invoices.'
        );
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance();

        // Checklist payments are invoice funding records. Their dated rows are
        // children, so list the source record once rather than duplicating it.
        $query = Payment::with(['purchaseOrder.vendor', 'vendor', 'paymentAccount', 'schedules' => fn ($query) => $query->where('status', '!=', 'cancelled')])
            ->whereNull('parent_payment_id')
            ->where(function ($query) {
                $query->where('status', 'pending_finance')
                    ->orWhere(function ($query) {
                        $query->where('status', 'scheduled')
                            ->where(function ($query) {
                                $query->whereDoesntHave('schedules')
                                    ->orWhereHas('schedules', fn ($schedules) => $schedules
                                        ->where('status', 'scheduled')
                                        ->whereNull('payment_authorisation_id'));
        \App\Support\RecordVisibility::apply($query, Auth::user());
                            });
                    });
            });

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($query) use ($search) {
                $query->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('bill_number', 'like', "%{$search}%")
                    ->orWhere('activity_reference', 'like', "%{$search}%")
                    ->orWhere('activity_name', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('purchaseOrder', fn ($order) => $order->where('po_number', 'like', "%{$search}%"));
            });
        }

        $payments      = $query->orderBy('account_name')->latest()->paginate(20)->withQueryString();
        return view('payments.index', compact('payments'));
    }

    public function create(Request $request): View
    {
        $this->authorizeFinance();

        // Payment scheduling is permitted only after the vendor bill has passed
        // the operational checklist and has been formally sent to Accounts.
        $vendors = \App\Models\Vendor::active()->orderBy('name')->get();
        $payees = Payee::where('active', true)->orderBy('name')->get();
        $accounts = PaymentAccount::where('active', true)->orderBy('name')->get();
        $channels = PaymentAuthorisationChannel::active()->with(['approvalChain', 'paymentAccount'])->orderBy('name')->get();

        $selectedOrderId = $request->purchase_order_id ?: $request->po;
        $selectedOrder = $selectedOrderId
            ? PurchaseOrder::whereHas('procurementChecklists', fn ($query) => $query->where('status', 'sent_to_accounts'))->with('vendor')->find($selectedOrderId)
            : null;

        return view('payments.create', compact('vendors', 'payees', 'accounts', 'channels', 'selectedOrder'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeFinance();

        // External payments use the same parent-and-schedule model as a
        // Checklist bill. The gross value and TDS stay on the parent, while
        // only the dated payment rows can move into authorisation.
        if (! $request->filled('purchase_order_id')) {
            return $this->storeExternalSchedule($request);
        }

        $request->validate([
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'vendor_id'         => ['nullable', 'exists:vendors,id'],
            'payee_id'          => ['nullable', 'exists:payees,id'],
            'payment_account_id'=> ['required', 'exists:payment_accounts,id'],
            'amount_due'        => ['required', 'numeric', 'min:1'],
            'schedule_month'    => ['required', 'date_format:Y-m'],
            'schedule_week'     => ['required', 'integer', 'between:1,5'],
            'payment_type'      => ['nullable', 'in:full,partial'],
            'tds_applied'       => ['nullable', 'boolean'],
            'tds_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'             => ['nullable', 'string'],
        ]);

        $po = $request->purchase_order_id ? PurchaseOrder::with(['vendor', 'rfqQuote.rfq.activityForm'])->findOrFail($request->purchase_order_id) : null;
        $date = $this->periodDate($request->schedule_month, (int) $request->schedule_week);
        $activity = $po?->rfqQuote?->rfq?->activityForm;
        $vendor = $po?->vendor ?: ($request->vendor_id ? \App\Models\Vendor::findOrFail($request->vendor_id) : null);
        $payee = $request->payee_id ? Payee::findOrFail($request->payee_id) : null;
        $account = PaymentAccount::findOrFail($request->payment_account_id);
        $tdsApplied = $request->boolean('tds_applied');
        $tdsRate = $tdsApplied ? (float) ($request->tds_rate ?? 0) : 0;
        $tdsAmount = round((float) $request->amount_due * $tdsRate / 100, 2);

        $payment = Payment::create([
            'payment_number'     => $this->nextPaymentNumber(),
            'purchase_order_id'  => $po?->id,
            'vendor_id'          => $vendor?->id,
            'payee_id'           => $payee?->id,
            'payment_account_id' => $account->id,
            'source'             => $po ? 'process' : 'manual',
            'payment_type'       => $po ? $request->input('payment_type', 'full') : 'full',
            'tds_applied'        => $tdsApplied,
            'tds_rate'           => $tdsRate,
            'tds_amount'         => $tdsAmount,
            'po_total'           => $po?->total_amount ?? 0,
            'amount_due'         => $request->amount_due,
            'net_amount'         => (float) $request->amount_due - $tdsAmount,
            'scheduled_date'     => $date,
            'schedule_month'     => $date->copy()->startOfMonth(),
            'schedule_week'      => (int) ceil($date->day / 7),
            'payment_method'     => 'pending_finance',
            'activity_name'      => $activity?->activity_name,
            'activity_reference' => $activity?->form_number,
            'account_name'       => $account->name,
            'notes'              => $request->notes,
            'bank_name'          => $vendor?->bank_name,
            'bank_account_number'=> $vendor?->bank_account_number,
            'status'             => 'scheduled',
            'created_by'         => Auth::id(),
        ]);

        AuditLog::record(Auth::user(), 'payment.created', $payment, $payment->payment_number);

        return redirect()->route('payments.show', $payment)
            ->with('success', "Payment schedule {$payment->payment_number} created.");
    }

    /** Import pending vendor invoices. Finance schedules each invoice in the normal process screen. */
    public function import(Request $request): RedirectResponse
    {
        $this->authorizeFinance();
        $this->authorizeVendorInvoiceImport();

        $request->validate([
            'payment_import' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        try {
            $sheets = Excel::toCollection(null, $request->file('payment_import'));
            $rows = $this->normaliseImportedPaymentRows($sheets->first() ?? collect());
            $invoices = $this->validateImportedPaymentRows($rows);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'payment_import' => 'The file could not be read. Download the template and keep its column headings unchanged.',
            ]);
        }

        $created = DB::transaction(function () use ($invoices) {
            $payments = collect();

            foreach ($invoices as $invoice) {
                $vendor = Vendor::active()->whereRaw('LOWER(email) = ?', [Str::lower($invoice['vendor_email'])])->firstOrFail();
                $invoiceDate = \Carbon\Carbon::createFromFormat('Y-m-d', $invoice['invoice_date']);
                $payment = Payment::create([
                    'payment_number' => $this->nextPaymentNumber(),
                    'vendor_id' => $vendor->id,
                    'created_by' => Auth::id(),
                    'source' => 'imported_vendor',
                    'payment_type' => 'full',
                    'tds_applied' => false,
                    'tds_rate' => 0,
                    'tds_amount' => 0,
                    'po_total' => 0,
                    'amount_due' => $invoice['gross_amount'],
                    'net_amount' => $invoice['gross_amount'],
                    // These dates preserve the invoice chronology only. Finance
                    // selects the actual payment periods during scheduling.
                    'scheduled_date' => $invoiceDate,
                    'schedule_month' => $invoiceDate->copy()->startOfMonth(),
                    'schedule_week' => (int) ceil($invoiceDate->day / 7),
                    'payment_method' => 'pending_finance',
                    'bill_number' => $invoice['invoice_number'],
                    'notes' => $invoice['notes'],
                    'bank_name' => $vendor->bank_name,
                    'bank_account_number' => $vendor->bank_account_number,
                    'status' => 'pending_finance',
                ]);

                AuditLog::record(Auth::user(), 'payment.invoice_imported', $payment, $payment->payment_number, [], [
                    'invoice_number' => $invoice['invoice_number'],
                    'invoice_date' => $invoice['invoice_date'],
                    'gross_amount' => $invoice['gross_amount'],
                ]);
                $payments->push($payment);
            }

            return $payments;
        });

        return redirect()->route('payments.index')->with('success', $created->count().' pending vendor invoice(s) imported. Open each invoice to set TDS and payment schedule.');
    }

    /** Download a safe, ready-to-fill CSV template for the importer. */
    public function importTemplate(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeFinance();
        $this->authorizeVendorInvoiceImport();

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Invoice Number', 'Vendor Email', 'Invoice Date', 'Gross Amount', 'Notes']);
            fputcsv($out, ['INV-2026-001', 'vendor@example.com', '2026-09-08', '100000', 'Pending invoice payment']);
            fputcsv($out, ['INV-2026-002', 'vendor@example.com', '2026-09-10', '62500', 'Second pending invoice']);
            fclose($out);
        }, 'pending-vendor-payment-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function show(Payment $payment): View
    {
        $this->authorizeFinance();

        $payment->load(['purchaseOrder.vendor', 'vendor', 'markedPaidBy', 'schedules.paymentAccount']);
        return view('payments.show', compact('payment'));
    }

    public function edit(Payment $payment): View
    {
        $this->authorizeFinance();

        abort_unless(in_array($payment->status, ['pending_finance', 'scheduled'], true), 403);
        if (in_array($payment->source, ['checklist', 'external', 'imported_vendor', 'direct_activity_form'], true) && ! $payment->parent_payment_id) {
            $payment->load(['purchaseOrder.vendor', 'vendorBill', 'schedules.paymentAccount']);
            $accounts = PaymentAccount::where('active', true)->orderBy('name')->get();
            $channels = PaymentAuthorisationChannel::active()->with(['approvalChain', 'paymentAccount'])->orderBy('name')->get();
            $vendors = Vendor::active()->orderBy('name')->get();
            $payees = Payee::where('active', true)->orderBy('name')->get();
            return view('payments.process', compact('payment', 'accounts', 'channels', 'vendors', 'payees'));
        }
        abort_if($payment->parent_payment_id, 403, 'Edit the parent checklist payment to manage its payment schedules.');
        $accounts = PaymentAccount::where('active', true)->orderBy('name')->get();
        return view('payments.edit', compact('payment', 'accounts'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeFinance();

        abort_unless(in_array($payment->status, ['pending_finance', 'scheduled'], true), 422);

        if (in_array($payment->source, ['checklist', 'external', 'imported_vendor', 'direct_activity_form'], true) && ! $payment->parent_payment_id) {
            return $this->updateChecklistSchedules($request, $payment);
        }

        $request->validate([
            'amount_due'     => ['required', 'numeric', 'min:1'],
            'schedule_month' => ['required', 'date_format:Y-m'],
            'schedule_week'  => ['required', 'integer', 'between:1,5'],
            'payment_type'   => ['required', 'in:full,partial'],
            'notes'          => ['nullable', 'string'],
            'payment_account_id' => ['required', 'exists:payment_accounts,id'],
            'tds_applied'    => ['nullable', 'boolean'],
            'tds_rate'       => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $date = $this->periodDate($request->schedule_month, (int) $request->schedule_week);
        $account = PaymentAccount::findOrFail($request->payment_account_id);
        $tdsApplied = $request->boolean('tds_applied');
        $tdsRate = $tdsApplied ? (float) ($request->tds_rate ?? 0) : 0;
        $tdsAmount = round((float) $request->amount_due * $tdsRate / 100, 2);

        if ($payment->parent_payment_id) {
            $parent = $payment->parentPayment;
            $other = (float) $parent->schedules()->whereKeyNot($payment->id)->whereNotIn('status', ['cancelled'])->sum('amount_due');
            abort_if((float) $request->amount_due > ((float) $parent->amount_due - $other + 0.0001), 422, 'The scheduled amount cannot exceed the unpaid checklist invoice balance.');
        }

        $payment->update(array_merge(
            $request->only(['amount_due', 'payment_type', 'notes']),
            ['payment_account_id' => $account->id, 'account_name' => $account->name, 'tds_applied' => $tdsApplied, 'tds_rate' => $tdsRate, 'tds_amount' => $tdsAmount, 'net_amount' => (float) $request->amount_due - $tdsAmount, 'status' => 'scheduled', 'scheduled_date' => $date, 'schedule_month' => $date->copy()->startOfMonth(), 'schedule_week' => (int) $request->schedule_week],
        ));
        AuditLog::record(Auth::user(), 'payment.updated', $payment, $payment->payment_number);

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment updated.');
    }

    public function markPaid(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizeFinance();
        abort_unless(
            auth()->user()->can('payments.mark_paid'),
            403,
            'You do not have permission to record completed payments.'
        );

        abort_unless($payment->status === 'authorised', 422, 'An approved Payment Authorisation is required before recording payment.');

        $request->validate([
            'actual_date'        => ['required', 'date'],
            'transaction_ref'    => ['nullable', 'string', 'max:100'],
            'payment_proof_note' => ['nullable', 'string'],
        ]);

        $payment->update([
            'status'             => 'paid',
            'amount_paid'        => $payment->net_amount ?: $payment->amount_due,
            'actual_date'        => $request->actual_date,
            'payment_reference'  => $request->transaction_ref,
            'notes'              => $request->payment_proof_note,
            'marked_paid_by'     => Auth::id(),
            'marked_paid_at'     => now(),
        ]);

        if ($payment->parentPayment) {
            $parent = $payment->parentPayment->load('schedules');
            if ($parent->schedules->where('status', '!=', 'cancelled')->every(fn (Payment $schedule) => $schedule->status === 'paid')) {
                $parent->update([
                    'status' => 'paid',
                    'amount_paid' => $parent->schedules->where('status', '!=', 'cancelled')->sum('amount_paid'),
                    'actual_date' => $payment->actual_date,
                    'marked_paid_by' => Auth::id(),
                    'marked_paid_at' => now(),
                ]);
            }
        }

        AuditLog::record(Auth::user(), 'payment.paid', $payment, $payment->payment_number);
        SendVendorPaymentPaidEmail::dispatch($payment);

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment marked as paid. The vendor has been notified.');
    }

    private function nextPaymentNumber(): string
    {
        return PaymentNumber::next();
    }

    /** Dates are stored for reporting, while users schedule by calendar month and week. */
    private function periodDate(string $month, int $week): \Carbon\Carbon
    {
        return \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth()->addDays(($week - 1) * 7);
    }

    /** Create an external payment with one or more controlled payment periods. */
    private function storeExternalSchedule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'vendor_id'          => ['nullable', 'exists:vendors,id'],
            'payee_id'           => ['nullable', 'exists:payees,id'],
            'payment_account_id' => ['required', 'exists:payment_accounts,id'],
            'amount_due'         => ['required', 'numeric', 'min:1'],
            'payment_type'       => ['required', 'in:full,partial'],
            'tds_applied'        => ['nullable', 'boolean'],
            'tds_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'schedule_month'     => ['required', 'array', 'min:1'],
            'schedule_month.*'   => ['required', 'date_format:Y-m'],
            'schedule_week'      => ['required', 'array', 'min:1'],
            'schedule_week.*'    => ['required', 'integer', 'between:1,5'],
            'schedule_amount'    => ['required', 'array', 'min:1'],
            'schedule_amount.*'  => ['required', 'numeric', 'min:0.01'],
            'schedule_channel_id' => ['required', 'array', 'min:1'],
            'schedule_channel_id.*' => ['required', 'exists:payment_authorisation_channels,id'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        abort_unless(count($data['schedule_month']) === count($data['schedule_week']) && count($data['schedule_week']) === count($data['schedule_amount']) && count($data['schedule_amount']) === count($data['schedule_channel_id']), 422, 'Each payment schedule row needs a month, week, amount, and authorisation channel.');
        abort_if($data['payment_type'] === 'full' && count($data['schedule_amount']) !== 1, 422, 'A full payment must have exactly one schedule row.');

        $tdsApplied = $request->boolean('tds_applied');
        $grossAmount = round((float) $data['amount_due'], 2);
        $tdsRate = $tdsApplied ? (float) ($data['tds_rate'] ?? 0) : 0;
        $tdsAmount = round($grossAmount * $tdsRate / 100, 2);
        $netPayable = round($grossAmount - $tdsAmount, 2);
        $scheduledTotal = round(collect($data['schedule_amount'])->sum(fn ($amount) => (float) $amount), 2);
        abort_unless(abs($scheduledTotal - $netPayable) < 0.01, 422, 'Scheduled payments must total the net payable amount of Rs '.number_format($netPayable, 2).'.');

        $account = PaymentAccount::findOrFail($data['payment_account_id']);
        $vendor = $data['vendor_id'] ? \App\Models\Vendor::findOrFail($data['vendor_id']) : null;
        $payee = $data['payee_id'] ? Payee::findOrFail($data['payee_id']) : null;

        $channels = PaymentAuthorisationChannel::active()->whereIn('id', $data['schedule_channel_id'])->get()->keyBy('id');
        abort_unless($channels->count() === count(collect($data['schedule_channel_id'])->unique()), 422, 'Choose an active Payment Authorisation Channel for every payment period.');
        $payment = DB::transaction(function () use ($data, $account, $vendor, $payee, $grossAmount, $tdsApplied, $tdsRate, $tdsAmount, $netPayable) {
            $firstDate = $this->periodDate($data['schedule_month'][0], (int) $data['schedule_week'][0]);
            $parent = Payment::create([
                'payment_number' => $this->nextPaymentNumber(), 'vendor_id' => $vendor?->id, 'payee_id' => $payee?->id,
                'payment_account_id' => $account->id, 'created_by' => Auth::id(), 'source' => 'external', 'payment_type' => $data['payment_type'],
                'tds_applied' => $tdsApplied, 'tds_rate' => $tdsRate, 'tds_amount' => $tdsAmount, 'po_total' => 0,
                'amount_due' => $grossAmount, 'net_amount' => $netPayable, 'scheduled_date' => $firstDate,
                'schedule_month' => $firstDate->copy()->startOfMonth(), 'schedule_week' => (int) $data['schedule_week'][0],
                'payment_method' => 'authorisation', 'account_name' => $account->name, 'notes' => $data['notes'] ?? null,
                'payment_authorisation_channel_id' => $data['schedule_channel_id'][0],
                'bank_name' => $vendor?->bank_name ?: $payee?->bank_name,
                'bank_account_number' => $vendor?->bank_account_number ?: $payee?->account_number,
                'status' => 'scheduled',
            ]);

            foreach ($data['schedule_amount'] as $index => $amount) {
                $date = $this->periodDate($data['schedule_month'][$index], (int) $data['schedule_week'][$index]);
                Payment::create([
                    'payment_number' => $this->nextPaymentNumber(), 'parent_payment_id' => $parent->id,
                    'vendor_id' => $vendor?->id, 'payee_id' => $payee?->id, 'payment_account_id' => $account->id, 'created_by' => Auth::id(),
                    'source' => 'external_schedule', 'payment_type' => $data['payment_type'], 'tds_applied' => false, 'tds_rate' => 0, 'tds_amount' => 0,
                    'po_total' => 0, 'amount_due' => $amount, 'net_amount' => $amount, 'scheduled_date' => $date,
                    'schedule_month' => $date->copy()->startOfMonth(), 'schedule_week' => (int) $data['schedule_week'][$index],
                    'payment_authorisation_channel_id' => $data['schedule_channel_id'][$index],
                    'payment_method' => 'authorisation', 'account_name' => $account->name, 'notes' => $data['notes'] ?? null,
                    'bank_name' => $vendor?->bank_name ?: $payee?->bank_name,
                    'bank_account_number' => $vendor?->bank_account_number ?: $payee?->account_number,
                    'status' => 'scheduled',
                ]);
            }

            AuditLog::record(Auth::user(), 'payment.created', $parent, $parent->payment_number, [], ['gross_amount' => $grossAmount, 'tds_amount' => $tdsAmount, 'net_payable' => $netPayable]);
            return $parent;
        });

        return redirect()->route('payments.show', $payment)->with('success', 'External payment schedule created.');
    }

    /** Schedule a checklist bill without changing its gross amount. */
    private function updateChecklistSchedules(Request $request, Payment $payment): RedirectResponse
    {
        $isDirectActivityPayment = $payment->source === 'direct_activity_form';
        $rules = [
            'payment_account_id' => ['required', 'exists:payment_accounts,id'],
            'payment_type'       => ['required', 'in:full,partial'],
            'tds_applied'        => ['nullable', 'boolean'],
            'tds_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'schedule_month'     => ['required', 'array', 'min:1'],
            'schedule_month.*'   => ['required', 'date_format:Y-m'],
            'schedule_week'      => ['required', 'array', 'min:1'],
            'schedule_week.*'    => ['required', 'integer', 'between:1,5'],
            'schedule_amount'    => ['required', 'array', 'min:1'],
            'schedule_amount.*'  => ['required', 'numeric', 'min:0.01'],
            'schedule_channel_id' => ['required', 'array', 'min:1'],
            'schedule_channel_id.*' => ['required', 'exists:payment_authorisation_channels,id'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];

        if ($isDirectActivityPayment) {
            $rules['amount_due'] = ['required', 'numeric', 'min:0.01'];
            $rules['vendor_id'] = ['nullable', 'exists:vendors,id'];
            $rules['payee_id'] = ['nullable', 'exists:payees,id'];
        }

        $data = $request->validate($rules);

        abort_unless(count($data['schedule_month']) === count($data['schedule_week']) && count($data['schedule_week']) === count($data['schedule_amount']) && count($data['schedule_amount']) === count($data['schedule_channel_id']), 422, 'Each payment schedule row needs a month, week, amount, and authorisation channel.');
        abort_if($data['payment_type'] === 'full' && count($data['schedule_amount']) !== 1, 422, 'A full payment must have exactly one schedule row.');

        $tdsApplied = $request->boolean('tds_applied');
        $tdsRate = $tdsApplied ? (float) ($data['tds_rate'] ?? 0) : 0;
        $grossAmount = round((float) ($isDirectActivityPayment ? $data['amount_due'] : $payment->amount_due), 2);
        $tdsAmount = round($grossAmount * $tdsRate / 100, 2);
        $netPayable = round($grossAmount - $tdsAmount, 2);
        $scheduledTotal = round(collect($data['schedule_amount'])->sum(fn ($amount) => (float) $amount), 2);


        $account = PaymentAccount::findOrFail($data['payment_account_id']);
        $channels = PaymentAuthorisationChannel::active()->whereIn('id', $data['schedule_channel_id'])->get()->keyBy('id');
        abort_unless($channels->count() === count(collect($data['schedule_channel_id'])->unique()), 422, 'Choose an active Payment Authorisation Channel for every payment period.');
        $vendor = $isDirectActivityPayment && ! empty($data['vendor_id']) ? Vendor::findOrFail($data['vendor_id']) : null;
        $payee = $isDirectActivityPayment && ! empty($data['payee_id']) ? Payee::findOrFail($data['payee_id']) : null;
        DB::transaction(function () use ($payment, $data, $account, $tdsApplied, $tdsRate, $tdsAmount, $netPayable, $grossAmount, $isDirectActivityPayment, $vendor, $payee) {
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);
            $existing = $locked->schedules()->lockForUpdate()->get();
            $committed = $existing->filter(fn (Payment $row) => $row->status !== 'cancelled' && ($row->status !== 'scheduled' || $row->payment_authorisation_id));
            if ($committed->isNotEmpty() && (
                abs((float)$locked->amount_due - $grossAmount) > 0.001 ||
                abs((float)$locked->net_amount - $netPayable) > 0.001 ||
                (bool)$locked->tds_applied !== $tdsApplied || abs((float)$locked->tds_rate - $tdsRate) > 0.001 ||
                $locked->payment_account_id !== $account->id ||
                ($isDirectActivityPayment && ($locked->vendor_id !== $vendor?->id || $locked->payee_id !== $payee?->id))
            )) throw ValidationException::withMessages(['schedule_amount'=>'Some instalments have entered authorisation or been paid. Keep the amount, TDS, account and recipient unchanged; edit only the remaining payment periods.']);
            $remaining = round($netPayable - (float)$committed->sum('net_amount'), 2);
            $total = round(array_sum($data['schedule_amount']), 2);
            if (abs($total - $remaining) >= 0.01) throw ValidationException::withMessages(['schedule_amount'=>'Remaining payment periods must total Rs '.number_format($remaining,2).'. Paid or authorised instalments are retained.']);
            $existing->filter(fn (Payment $row) => $row->status === 'scheduled' && !$row->payment_authorisation_id)->each->delete();

            if ($isDirectActivityPayment) {
                $locked->update([
                    'vendor_id' => $vendor?->id,
                    'payee_id' => $payee?->id,
                    'bank_name' => $vendor?->bank_name ?: $payee?->bank_name,
                    'bank_account_number' => $vendor?->bank_account_number ?: $payee?->account_number,
                ]);
            }

            foreach ($data['schedule_amount'] as $index => $amount) {
                $date = $this->periodDate($data['schedule_month'][$index], (int) $data['schedule_week'][$index]);
                Payment::create([
                    'payment_number' => $this->nextPaymentNumber(), 'parent_payment_id' => $locked->id,
                    'purchase_order_id' => $locked->purchase_order_id, 'activity_form_id' => $locked->activity_form_id, 'vendor_id' => $locked->vendor_id, 'payee_id' => $locked->payee_id, 'vendor_bill_id' => $locked->vendor_bill_id,
                    'payment_account_id' => $account->id, 'created_by' => Auth::id(), 'source' => match ($locked->source) {
                        'external' => 'external_schedule',
                        'imported_vendor' => 'imported_vendor_schedule',
                        'direct_activity_form' => 'direct_activity_form_schedule',
                        default => 'checklist_schedule',
                    }, 'payment_type' => $data['payment_type'],
                    'tds_applied' => false, 'tds_rate' => 0, 'tds_amount' => 0, 'po_total' => $locked->po_total,
                    'amount_due' => $amount, 'net_amount' => $amount, 'scheduled_date' => $date, 'schedule_month' => $date->copy()->startOfMonth(), 'schedule_week' => (int) $data['schedule_week'][$index],
                    'payment_authorisation_channel_id' => $data['schedule_channel_id'][$index],
                    'payment_method' => 'authorisation', 'bill_number' => $locked->bill_number, 'activity_name' => $locked->activity_name, 'activity_reference' => $locked->activity_reference,
                    'account_name' => $account->name, 'bank_name' => $locked->bank_name, 'bank_account_number' => $locked->bank_account_number,
                    'status' => 'scheduled', 'notes' => $data['notes'] ?? null,
                ]);
            }
            $firstDate = $this->periodDate($data['schedule_month'][0], (int) $data['schedule_week'][0]);
            $locked->update(array_merge([
                'payment_account_id' => $account->id, 'account_name' => $account->name, 'payment_type' => $data['payment_type'],
                'tds_applied' => $tdsApplied, 'tds_rate' => $tdsRate, 'tds_amount' => $tdsAmount, 'net_amount' => $netPayable,
                'scheduled_date' => $firstDate, 'schedule_month' => $firstDate->copy()->startOfMonth(), 'schedule_week' => (int) $data['schedule_week'][0],
                'payment_authorisation_channel_id' => $data['schedule_channel_id'][0],
                'status' => 'scheduled', 'notes' => $data['notes'] ?? $locked->notes,
            ], $isDirectActivityPayment ? ['amount_due' => $grossAmount] : []));
            AuditLog::record(Auth::user(), 'payment.schedules_updated', $locked, $locked->payment_number, [], ['payment_type' => $data['payment_type'], 'gross_amount' => $locked->amount_due, 'tds_amount' => $tdsAmount, 'net_payable' => $netPayable, 'scheduled_total' => $netPayable]);
        });

        $message = $payment->source === 'checklist'
            ? 'Payment schedule saved. The Checklist bill amount has not changed.'
            : 'Payment schedule saved. The gross amount and net payable are retained.';

        if ($payment->source === 'imported_vendor') {
            SendVendorPaymentScheduledEmail::dispatch($payment);
            $message .= ' The vendor has been notified of the schedule.';
        }

        return redirect()->route('payments.show', $payment)->with('success', $message);
    }

    /** Convert an uploaded sheet into heading-keyed rows, retaining source row numbers for useful errors. */
    private function normaliseImportedPaymentRows(iterable $sheet): Collection
    {
        $sheetRows = collect($sheet)->values();
        $header = collect($sheetRows->shift() ?? [])
            ->map(fn ($value) => trim(Str::lower(preg_replace('/[^a-z0-9]+/i', '_', (string) $value)), '_'))
            ->values();

        $required = ['invoice_number', 'vendor_email', 'invoice_date', 'gross_amount'];
        $missing = array_values(array_diff($required, $header->all()));
        if ($missing) {
            throw ValidationException::withMessages(['payment_import' => 'Missing column(s): '.implode(', ', $missing).'. Download the template and use its headings.']);
        }
        if ($header->filter()->count() !== $header->filter()->unique()->count()) {
            throw ValidationException::withMessages(['payment_import' => 'Each import column heading must appear only once.']);
        }

        return $sheetRows->map(function ($values, int $index) use ($header) {
            $row = ['_row' => $index + 2];
            foreach ($header as $column => $name) {
                if ($name !== '') $row[$name] = is_string($values[$column] ?? null) ? trim($values[$column]) : ($values[$column] ?? null);
            }
            return $row;
        })->filter(fn (array $row) => collect($row)->except('_row')->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())->values();
    }

    /** Validate all rows before any payment is written, so an import is always all-or-nothing. */
    private function validateImportedPaymentRows(Collection $rows): Collection
    {
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages(['payment_import' => 'The import file has no payment rows.']);
        }

        $errors = [];
        $normalised = $rows->map(function (array $row) use (&$errors) {
            $rowNumber = $row['_row'];
            $invoiceNumber = trim((string) ($row['invoice_number'] ?? ''));
            $email = Str::lower(trim((string) ($row['vendor_email'] ?? '')));
            $invoiceDate = trim((string) ($row['invoice_date'] ?? ''));
            $gross = $this->importAmount($row['gross_amount'] ?? null);

            if ($invoiceNumber === '') $errors[] = "Row {$rowNumber}: Invoice Number is required.";
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Row {$rowNumber}: Vendor Email must be a valid email address.";
            if (! preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $invoiceDate) || ! \Carbon\Carbon::createFromFormat('Y-m-d', $invoiceDate)) $errors[] = "Row {$rowNumber}: Invoice Date must use YYYY-MM-DD.";
            if ($gross === null || $gross <= 0) $errors[] = "Row {$rowNumber}: Gross Amount must be greater than zero.";

            return [
                'row' => $rowNumber, 'invoice_number' => $invoiceNumber, 'vendor_email' => $email, 'invoice_date' => $invoiceDate,
                'gross_amount' => $gross,
                'notes' => trim((string) ($row['notes'] ?? '')) ?: null,
            ];
        });

        foreach ($normalised as $invoice) {
            if (! Vendor::active()->whereRaw('LOWER(email) = ?', [$invoice['vendor_email']])->exists()) $errors[] = "Row {$invoice['row']}: no active vendor matches {$invoice['vendor_email']}.";
        }
        foreach ($normalised->groupBy(fn (array $invoice) => $invoice['vendor_email'].'|'.Str::lower($invoice['invoice_number'])) as $key => $duplicates) {
            if ($duplicates->count() > 1) $errors[] = 'Invoice '.$duplicates->first()['invoice_number'].' appears more than once for '.$duplicates->first()['vendor_email'].'.';
        }

        if ($errors) throw ValidationException::withMessages(['payment_import' => array_slice($errors, 0, 12)]);

        return $normalised;
    }

    private function importAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        $number = preg_replace('/[^0-9.\\-]/', '', (string) $value);
        return $number !== '' && is_numeric($number) ? round((float) $number, 2) : null;
    }
}
