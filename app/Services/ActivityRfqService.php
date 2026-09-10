<?php
namespace App\Services;
use App\Models\ActivityForm;
use App\Models\Rfq;
use App\Support\PaymentNumber;
use Illuminate\Support\Facades\DB;

class ActivityRfqService {
    public function handoff(ActivityForm $activity): Rfq {
        return DB::transaction(function () use ($activity) {
            $activity = ActivityForm::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            abort_unless($activity->isApproved(), 422);
            if ($existing = Rfq::withTrashed()->where('handoff_activity_id',$activity->id)->first()) return $existing;
            return Rfq::firstOrCreate(['handoff_activity_id'=>$activity->id], [
                'rfq_number'=>PaymentNumber::nextRfq(), 'activity_form_id'=>$activity->id,
                'assigned_to'=>\App\Models\Setting::get('rfq_preparer_user_id') ?: null, 'budget_id'=>$activity->budget_id, 'title'=>$activity->activity_name,
                'notes'=>$activity->remarks, 'created_by'=>$activity->creator_id, 'status'=>'draft',
                'preparation_items'=>$activity->lineItems->map(fn($i)=>[
                    'source_line_item_id'=>$i->id,'description'=>$i->item_name,'quantity'=>(float)$i->quantity,
                    'unit'=>$i->unit,'request_remarks'=>$i->item_remarks,'vendor_ids'=>[],
                ])->values()->all(),
            ]);
        });
    }
}
