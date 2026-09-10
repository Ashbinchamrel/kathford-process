<?php
namespace App\Services;

use App\Models\{ActivityForm, Payment, AuditLog};
use App\Support\PaymentNumber;
use Illuminate\Support\Facades\{DB, Auth};

class ActivityPaymentService
{
    public function handoff(ActivityForm $activity): Payment
    {
        return DB::transaction(function () use ($activity) {
            $activity = ActivityForm::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            abort_unless($activity->isApproved() && $activity->category->bypasses_procurement_to_payment, 422);
            $existing = Payment::withTrashed()->where('activity_form_id', $activity->id)
                ->where('source', 'direct_activity_form')->whereNull('parent_payment_id')->first();
            if ($existing) return $existing;
            $amount = round((float) $activity->total_estimated_amount, 2);
            $payment = Payment::create([
                'payment_number'=>PaymentNumber::next(), 'activity_form_id'=>$activity->id,
                'created_by'=>Auth::id() ?? $activity->creator_id, 'source'=>'direct_activity_form',
                'payment_type'=>'full', 'amount_due'=>$amount, 'net_amount'=>$amount, 'po_total'=>0,
                'scheduled_date'=>today(), 'schedule_month'=>today()->startOfMonth(),
                'schedule_week'=>(int)ceil(today()->day/7), 'payment_method'=>'pending_finance',
                'status'=>'pending_finance', 'activity_name'=>$activity->activity_name,
                'activity_reference'=>$activity->form_number,
                'notes'=>'Bypass Procurement: fully approved activity forwarded to Accounts for scheduling.',
            ]);
            AuditLog::record(Auth::user(), 'payment.activity_handoff', $payment, $activity->form_number);
            return $payment;
        });
    }
}
