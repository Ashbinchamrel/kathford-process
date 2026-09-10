<?php

namespace App\Http\Controllers;

use App\Jobs\SendVendorPaymentAuthorisedEmail;
use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\PaymentAuthorisation;
use App\Models\Setting;
use App\Services\ApprovalService;
use App\Support\PaymentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentAuthorisationController extends Controller
{
    public function __construct(private readonly ApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        $this->authorizePermission('payment_authorisations.view');
        $query = PaymentAuthorisation::with(['createdBy', 'approvalChain', 'payments.vendor', 'paymentAuthorisationChannel']);
        \App\Support\RecordVisibility::apply($query, Auth::user());

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($query) use ($search) {
                $query->where('authorisation_number', 'like', "%{$search}%")
                    ->orWhereHas('paymentAuthorisationChannel', fn ($channel) => $channel->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('approvalChain', fn ($chain) => $chain->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('month') && preg_match('/^\\d{4}-\\d{2}$/', $request->month)) {
            $query->whereDate('schedule_month', $request->month.'-01');
        }

        $authorisations = $query->latest()->paginate(20)->withQueryString();
        return view('payment-authorisations.index', compact('authorisations'));
    }

    public function create(Request $request): View
    {
        $this->authorizePermission('payment_authorisations.create');
        $month = $request->input('month', now()->format('Y-m'));
        $week = (int) $request->input('week', (int) ceil(now()->day / 7));
        $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $payments = Payment::with(['vendor', 'purchaseOrder', 'paymentAccount', 'paymentAuthorisationChannel.approvalChain'])
            ->where('status', 'scheduled')->whereNull('payment_authorisation_id')
            // Parent records retain the gross amount. Only their dated child
            // schedules belong in a weekly authorisation batch.
            ->where(fn ($query) => $query->whereNotNull('parent_payment_id')->orWhereNotIn('source', ['checklist', 'external', 'imported_vendor', 'direct_activity_form']))
            ->whereDate('schedule_month', $start->toDateString())->where('schedule_week', $week)
            ->whereNotNull('payment_authorisation_channel_id')
            ->orderBy('payment_authorisation_channel_id')->orderBy('account_name')->orderBy('scheduled_date')->get();
        return view('payment-authorisations.create', compact('month', 'week', 'payments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('payment_authorisations.create');
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'week' => ['required', 'integer', 'between:1,5'],
            'payment_ids' => ['required', 'array', 'min:1'],
            'payment_ids.*' => ['exists:payments,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'submit_for_approval' => ['nullable', 'boolean'],
        ]);
        if ($request->boolean('submit_for_approval')) {
            $this->authorizePermission('payment_authorisations.submit');
        }

        $month = \Carbon\Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth();
        $paymentIds = collect($data['payment_ids'])->unique()->values();
        $authorisations = DB::transaction(function () use ($data, $month, $paymentIds) {
            // Lock selected schedules so a second Finance user cannot include
            // the same row in another authorisation at the same time.
            $payments = Payment::with('paymentAuthorisationChannel.approvalChain')
                ->whereIn('id', $paymentIds)
                ->where('status', 'scheduled')->whereNull('payment_authorisation_id')
                ->where(fn ($query) => $query->whereNotNull('parent_payment_id')->orWhereNotIn('source', ['checklist', 'external', 'imported_vendor', 'direct_activity_form']))
                ->whereDate('schedule_month', $month->toDateString())->where('schedule_week', $data['week'])
                ->lockForUpdate()->get();

            if ($payments->count() !== $paymentIds->count()) {
                throw ValidationException::withMessages([
                    'payment_ids' => 'One or more selected payments are no longer available. Reload the scheduled payments and select them again.',
                ]);
            }

            $byChannel = $payments->groupBy('payment_authorisation_channel_id');
            $invalidChannel = $byChannel->first(function ($channelPayments) {
                $channel = $channelPayments->first()->paymentAuthorisationChannel;
                return ! $channel?->is_active || ! $channel->approvalChain?->is_active;
            });
            if ($invalidChannel) {
                throw ValidationException::withMessages([
                    'payment_ids' => 'One selected Payment Authorisation Channel is inactive or has no active approval chain. Update the channel, then try again.',
                ]);
            }

            return $byChannel->map(function ($channelPayments) use ($data, $month) {
                $channel = $channelPayments->first()->paymentAuthorisationChannel;
                $authorisation = PaymentAuthorisation::create([
                    'authorisation_number' => $this->nextNumber(),
                    'schedule_month' => $month,
                    'schedule_week' => $data['week'],
                    'total_amount' => $channelPayments->sum('net_amount'),
                    'notes' => $data['notes'] ?? null,
                    'created_by' => Auth::id(),
                    'payment_authorisation_channel_id' => $channel->id,
                    'approval_chain_id' => $channel->approval_chain_id,
                    'status' => 'generated',
                ]);
                $channelPayments->each(fn (Payment $payment) => $payment->update([
                    'payment_authorisation_id' => $authorisation->id,
                    'status' => 'in_authorisation',
                ]));
                AuditLog::record(Auth::user(), 'payment_authorisation.created', $authorisation, $authorisation->authorisation_number, [], [
                    'channel' => $channel->name,
                    'payment_count' => $channelPayments->count(),
                ]);

                return $authorisation;
            })->values();
        }, 5);

        if ($request->boolean('submit_for_approval')) {
            $authorisations->each(fn (PaymentAuthorisation $authorisation) => $this->submitAuthorisation($authorisation));
        }

        if ($authorisations->count() === 1) {
            return redirect()->route('payment-authorisations.show', $authorisations->first())
                ->with('success', $request->boolean('submit_for_approval') ? 'Payment Authorisation created and submitted for approval.' : 'Payment Authorisation created.');
        }

        return redirect()->route('payment-authorisations.index')->with('success', sprintf(
            '%d Payment Authorisations were created%s—one for each selected channel.',
            $authorisations->count(),
            $request->boolean('submit_for_approval') ? ' and submitted for approval ' : ' '
        ));
    }

    public function show(PaymentAuthorisation $paymentAuthorisation): View
    {
        $this->authorizePermission('payment_authorisations.view');
        $paymentAuthorisation->load(['createdBy', 'approvalChain.verifiers', 'approvalChain.approvers', 'paymentAuthorisationChannel', 'payments.vendor', 'payments.purchaseOrder', 'approvalActions.actor']);
        return view('payment-authorisations.show', compact('paymentAuthorisation'));
    }

    public function submit(PaymentAuthorisation $paymentAuthorisation): RedirectResponse
    {
        $this->authorizeOwner($paymentAuthorisation);
        return $this->submitAuthorisation($paymentAuthorisation);
    }

    public function verify(Request $request, PaymentAuthorisation $paymentAuthorisation): RedirectResponse
    {
        $this->authorizeVerifier($paymentAuthorisation);
        $data = $request->validate(['decision' => ['required', 'in:approved,modified_approved,rejected'], 'note' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected']]);
        $this->approvalService->verify($paymentAuthorisation, Auth::user(), $data['decision'], $data['note'] ?? null);
        return back()->with('success', 'Verification action recorded.');
    }

    public function approve(Request $request, PaymentAuthorisation $paymentAuthorisation): RedirectResponse
    {
        $this->authorizeApprover($paymentAuthorisation);
        $data = $request->validate(['decision' => ['required', 'in:approved,modified_approved,rejected'], 'note' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected']]);
        $this->approvalService->approve($paymentAuthorisation, Auth::user(), $data['decision'], $data['note'] ?? null);
        if ($data['decision'] === 'approved' && $paymentAuthorisation->fresh()->status === 'approved') {
            $paymentAuthorisation->load('payments.vendor');
            foreach ($paymentAuthorisation->payments as $payment) { $payment->update(['status' => 'authorised']); SendVendorPaymentAuthorisedEmail::dispatch($payment); }
            AuditLog::record(Auth::user(), 'payment_authorisation.approved', $paymentAuthorisation, $paymentAuthorisation->authorisation_number);
            return back()->with('success', 'Payment Authorisation approved. Vendors have been notified and the bank CSV is ready.');
        }
        return back()->with('success', 'Approval action recorded.');
    }

    public function csv(PaymentAuthorisation $paymentAuthorisation): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizePermission('payment_authorisations.export');
        if ($paymentAuthorisation->status !== 'approved') {
            return redirect()->route('payment-authorisations.show', $paymentAuthorisation)
                ->withErrors(['authorisation' => 'The bank CSV becomes available after this authorisation is approved.']);
        }
        $paymentAuthorisation->load('payments.vendor', 'payments.payee');
        $paymentAuthorisation->update(['csv_exported_at' => now()]);
        return response()->streamDownload(function () use ($paymentAuthorisation) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Amount', 'Bank Name', 'Bank Account Number', 'Account Name', 'Remarks', 'Bill Number', 'Activity Name / Reference']);
            foreach ($paymentAuthorisation->payments as $payment) {
                $remarks = trim(($payment->notes ?: '').($payment->sub_account ? ' | Sub account: '.$payment->sub_account : ''));
                fputcsv($out, [number_format($payment->net_amount ?: $payment->amount_due, 2, '.', ''), $payment->bank_name, $payment->bank_account_number, $payment->vendor?->bank_account_name ?: $payment->payee?->account_name ?: $payment->account_name, $remarks, $payment->bill_number, trim(($payment->activity_name ?: '').' / '.($payment->activity_reference ?: ''))]);
            }
            fclose($out);
        }, "{$paymentAuthorisation->authorisation_number}.csv", ['Content-Type' => 'text/csv']);
    }

    private function submitAuthorisation(PaymentAuthorisation $authorisation): RedirectResponse
    {
        $chain = $authorisation->approvalChain;
        if (! $chain?->is_active) return redirect()->route('payment-authorisations.show', $authorisation)->withErrors(['approval_chain' => 'This authorisation channel has no active approval chain.']);
        $this->approvalService->submit($authorisation, Auth::user());
        return redirect()->route('payment-authorisations.show', $authorisation)->with('success', 'Payment Authorisation submitted for verification.');
    }

    private function nextNumber(): string { return PaymentNumber::nextPaymentAuthorisation(); }
    private function authorizePermission(string $permission): void { abort_unless(Auth::user()->can($permission), 403, 'You do not have permission to perform this action.'); }
    private function authorizeOwner(PaymentAuthorisation $item): void { $this->authorizePermission('payment_authorisations.submit'); abort_unless($item->isEditable() && (Auth::user()->isSuperAdmin() || $item->created_by === Auth::id()), 403); }
    private function authorizeVerifier(PaymentAuthorisation $item): void { $this->authorizePermission('payment_authorisations.verify'); abort_unless($this->approvalService->canVerify($item, Auth::user()), 403); }
    private function authorizeApprover(PaymentAuthorisation $item): void { $this->authorizePermission('payment_authorisations.approve'); abort_unless($this->approvalService->canApprove($item, Auth::user()), 403); }
}
