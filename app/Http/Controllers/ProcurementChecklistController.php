<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\ProcurementChecklist;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\DocumentBranding;
use App\Support\PaymentNumber;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcurementChecklistController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): View
    {
        $this->authorizeChecklistUser();
        $query = ProcurementChecklist::with(['purchaseOrder.vendor', 'vendorBill'])
            ->latest();
        \App\Support\RecordVisibility::apply($query, Auth::user());
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('search')) {
            $search = '%'.trim($request->string('search')->toString()).'%';
            $query->where(fn ($q) => $q
                ->whereHas('purchaseOrder', fn ($po) => $po->where('po_number', 'like', $search))
                ->orWhereHas('vendorBill', fn ($bill) => $bill->where('bill_number', 'like', $search))
                ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', $search)));
        }

        $checklists = $query->paginate(20)->withQueryString();
        return view('checklists.index', compact('checklists'));
    }

    public function show(ProcurementChecklist $checklist): View
    {
        $this->authorizeChecklistUser();
        $checklist->load([
            'vendorBill', 'vendor', 'purchaseOrder.items', 'purchaseOrder.approvalActions.actor',
            'purchaseOrder.rfqQuote.vendor', 'purchaseOrder.rfqQuote.items',
            'purchaseOrder.rfqQuote.rfq.activityForm.category',
            'purchaseOrder.rfqQuote.rfq.activityForm.department',
            'purchaseOrder.rfqQuote.rfq.activityForm.approvalActions.actor',
            'completedBy', 'accountsSentBy', 'checklistPayment',
        ]);
        $questionSets = ['goods'=>\App\Models\ChecklistQuestion::snapshot('goods'),'service'=>\App\Models\ChecklistQuestion::snapshot('service')];
        $questionSets[$checklist->fulfillment_type] = $checklist->question_snapshot ?? collect($checklist->requiredChecks())->map(fn($label)=>['label'=>$label,'required'=>true])->all();
        $initialAnswers = $checklist->answers ?? collect($checklist->requiredChecks())->mapWithKeys(fn($label,$key)=>[$key=>$checklist->answerChecked($key)])->all();
        return view('checklists.show', compact('checklist','questionSets','initialAnswers'));
    }

    public function update(Request $request, ProcurementChecklist $checklist): RedirectResponse
    {
        $this->authorizeChecklistUser();
        abort_unless(! $checklist->isSentToAccounts() && $checklist->status !== 'returned', 422, 'This checklist has already been sent to Accounts and is locked.');

        $data = $request->validate([
            'fulfillment_type' => ['required', 'in:goods,service'],
            'control_comments' => ['nullable', 'string', 'max:3000'],
        ]);
        $snapshot = $checklist->question_snapshot;
        if ($snapshot === null || $checklist->fulfillment_type !== $data['fulfillment_type']) {
            $snapshot = $checklist->fulfillment_type === $data['fulfillment_type'] ? collect($checklist->requiredChecks())->map(fn($label)=>['label'=>$label,'required'=>true])->all() : \App\Models\ChecklistQuestion::snapshot($data['fulfillment_type']);
        }
        $data['question_snapshot']=$snapshot;
        $data['answers']=collect($snapshot)->mapWithKeys(fn($question,$key)=>[$key=>$request->boolean('answers.'.$key)])->all();

        $checklist->fill($data);
        if ($checklist->controlsComplete()) {
            $checklist->fill([
                'status' => 'ready_for_accounts',
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);
        } else {
            $checklist->fill([
                'status' => 'pending_controls',
                'completed_by' => null,
                'completed_at' => null,
            ]);
        }
        $checklist->save();

        AuditLog::record(Auth::user(), 'procurement_checklist.updated', $checklist, $checklist->purchaseOrder?->po_number, [], [
            'status' => $checklist->status,
            'fulfillment_type' => $checklist->fulfillment_type,
        ]);

        return back()->with('success', $checklist->isReadyForAccounts()
            ? 'All required controls are complete. This checklist is ready to send to Accounts.'
            : 'Checklist progress saved. Complete every required control before sending to Accounts.');
    }

    public function sendToAccounts(Request $request, ProcurementChecklist $checklist): RedirectResponse
    {
        $this->authorizeChecklistUser();
        $data = $request->validate(['accounts_comment' => ['nullable', 'string', 'max:3000']]);

        [$payment, $created] = DB::transaction(function () use ($checklist, $data): array {
            // A row lock keeps a double-click or two Finance users from making
            // more than one payable record for the same vendor invoice.
            $locked = ProcurementChecklist::query()->lockForUpdate()->findOrFail($checklist->id);
            abort_if($locked->status === 'returned' || $locked->vendorBill?->status === 'returned', 422, 'This invoice has been returned to the vendor.');
            $existingPayment = Payment::query()
                ->where('source', 'checklist')
                ->where('vendor_bill_id', $locked->vendor_bill_id)
                ->whereNull('parent_payment_id')
                ->first();

            if ($existingPayment) {
                return [$existingPayment, false];
            }

            // A former failed request could have marked the checklist as sent
            // before failing to create its payment. Allow this safe recovery.
            abort_unless(
                $locked->isReadyForAccounts() || $locked->isSentToAccounts(),
                422,
                'Complete every checklist control before sending this bill to Accounts.'
            );

            $locked->update([
                'status' => 'sent_to_accounts',
                'accounts_sent_by' => Auth::id(),
                'accounts_sent_at' => now(),
                'accounts_comment' => $data['accounts_comment'] ?? null,
            ]);

            $payment = Payment::create([
                'payment_number' => $this->nextPaymentNumber(),
                'purchase_order_id' => $locked->purchase_order_id,
                'vendor_id' => $locked->vendor_id,
                'vendor_bill_id' => $locked->vendor_bill_id,
                'created_by' => Auth::id(),
                'source' => 'checklist',
                'payment_type' => 'full',
                'po_total' => $locked->purchaseOrder->total_amount,
                'amount_due' => $locked->vendorBill->amount,
                'net_amount' => $locked->vendorBill->amount,
                // Finance chooses the actual date and method in Payments.
                'scheduled_date' => today(),
                'schedule_month' => today()->startOfMonth(),
                'schedule_week' => (int) ceil(today()->day / 7),
                'payment_method' => 'pending_finance',
                'bill_number' => $locked->vendorBill->bill_number,
                'activity_name' => $locked->purchaseOrder->rfqQuote?->rfq?->activityForm?->activity_name,
                'activity_reference' => $locked->purchaseOrder->rfqQuote?->rfq?->activityForm?->form_number ?? $locked->purchaseOrder->rfqQuote?->rfq?->rfq_number,
                'account_name' => $locked->vendor->bank_account_name,
                'bank_name' => $locked->vendor->bank_name,
                'bank_account_number' => $locked->vendor->bank_account_number,
                'status' => 'pending_finance',
                'notes' => "Created automatically from checklist for bill {$locked->vendorBill->bill_number}. ".($locked->accounts_comment ?? ''),
            ]);

            AuditLog::record(Auth::user(), 'procurement_checklist.sent_to_accounts', $locked, $locked->purchaseOrder?->po_number, [], [
                'comment' => $locked->accounts_comment,
                'payment_number' => $payment->payment_number,
            ]);

            return [$payment, true];
        }, 5);

        if (! $created) {
            return redirect()->route('checklists.show', $checklist)
                ->with('info', 'This bill was already sent to Accounts. Its existing payment record was kept unchanged.');
        }

        $recipients = User::active()->whereHas('role', fn ($query) => $query->whereIn('name', ['finance', 'super_admin']))->get();
        $this->notifications->sendToMany(
            $recipients->all(),
            'checklist_sent_to_accounts',
            'Bill cleared for payment processing',
            "{$checklist->purchaseOrder->po_number} / bill {$checklist->vendorBill->bill_number} has passed the control checklist and was sent to Accounts.",
            route('payments.show', $payment),
        );

        return back()->with('success', 'Checklist sent to Accounts for payment processing.');
    }

    public function returnToVendor(Request $request, ProcurementChecklist $checklist): RedirectResponse
    {
        $data=$request->validate(['reason'=>['required','string','max:3000'],'return_type'=>['required','in:goods_and_invoice,invoice,service_correction']]);
        $return=DB::transaction(function() use($data,$checklist) {
            $locked=ProcurementChecklist::whereKey($checklist->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status==='returned',422,'This invoice has already been returned.');
            $payments=Payment::where(fn($q)=>$q->where('vendor_bill_id',$locked->vendor_bill_id)->orWhereHas('parentPayment',fn($parent)=>$parent->where('vendor_bill_id',$locked->vendor_bill_id)))->lockForUpdate()->get();
            abort_if($payments->contains(fn($p)=>$p->status==='paid'||(float)$p->amount_paid>0||$p->payment_authorisation_id),422,'This invoice has payments or an approval batch. Finance must resolve those before it can be returned.');
            foreach($payments as $payment) $payment->update(['status'=>'cancelled']);
            $locked->vendorBill->update(['status'=>'returned']);
            $locked->purchaseOrder->update(['status'=>'partially_received']);
            $locked->update(['status'=>'returned','accounts_sent_at'=>null,'accounts_sent_by'=>null]);
            $return=\App\Models\ProcurementReturn::create($data+['checklist_id'=>$locked->id,'vendor_id'=>$locked->vendor_id,'returned_by'=>Auth::id()]);
            AuditLog::record(Auth::user(),'procurement.returned_to_vendor',$locked,$locked->purchaseOrder?->po_number,[], $data);
            return $return;
        });
        \App\Jobs\SendProcurementReturnEmail::dispatch($return);
        return back()->with('success','Return recorded. The vendor can see the return in their portal, and an email notification has been queued.');
    }

    public function pdf(ProcurementChecklist $checklist): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeChecklistUser();
        $checklist->load([
            'vendorBill', 'vendor', 'purchaseOrder.items', 'purchaseOrder.approvalActions.actor',
            'purchaseOrder.rfqQuote.vendor', 'purchaseOrder.rfqQuote.items',
            'purchaseOrder.rfqQuote.rfq.activityForm.category',
            'purchaseOrder.rfqQuote.rfq.activityForm.department',
            'purchaseOrder.rfqQuote.rfq.activityForm.approvalActions.actor',
            'completedBy', 'accountsSentBy',
        ]);
        $auditLogs = AuditLog::where('model_type', ProcurementChecklist::class)
            ->where('model_id', $checklist->id)
            ->orderBy('logged_at')
            ->get();

        return Pdf::loadView('checklists.pdf', array_merge(compact('checklist', 'auditLogs'), DocumentBranding::data()))
            ->setPaper('a4', 'portrait')
            ->download("Checklist-{$checklist->purchaseOrder->po_number}-{$checklist->vendorBill->bill_number}.pdf");
    }

    /** Anyone who can work this checklist can pull the Activity Form behind it, regardless of chain/ownership. */
    public function activityFormPdf(ProcurementChecklist $checklist): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeChecklistUser();
        $checklist->load('purchaseOrder.rfqQuote.rfq.activityForm');
        $form = $checklist->purchaseOrder?->rfqQuote?->rfq?->activityForm;
        abort_unless($form, 404);

        $form->load(['category', 'creator', 'department', 'budget', 'verifier', 'approver', 'approvalActions.actor', 'lineItems.vendor']);

        return Pdf::loadView('activity-forms.pdf', array_merge(['form' => $form], DocumentBranding::data()))
            ->setPaper('a4', 'portrait')
            ->download("{$form->form_number}.pdf");
    }

    private function authorizeChecklistUser(): void
    {
        abort_unless(
            Auth::user()->isSuperAdmin()
                || Auth::user()->can('checklists.view')
                || Auth::user()->can('checklists.complete'),
            403,
            'You do not have procurement-checklist access.'
        );
    }

    private function nextPaymentNumber(): string
    {
        return PaymentNumber::next();
    }
}
