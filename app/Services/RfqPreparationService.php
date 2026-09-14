<?php
namespace App\Services;
use App\Models\{Rfq,RfqItem,RfqQuote,RfqQuoteItem,DepartmentBudget,Vendor,VendorRate,Payment,AuditLog};
use App\Support\PaymentNumber;
use Illuminate\Support\Facades\{DB,Auth,Gate};
use Illuminate\Validation\ValidationException;

class RfqPreparationService {
    public function save(array $data): Rfq {
        return DB::transaction(function () use ($data) {
            $rfq = !empty($data['rfq_id']) ? Rfq::whereKey($data['rfq_id'])->lockForUpdate()->firstOrFail() : null;
            if ($rfq) {
                abort_unless($rfq->handoff_activity_id && $rfq->status === 'draft' && !$rfq->requestItems()->exists() && !$rfq->quotes()->exists(),422,'This RFQ has already been prepared.');
                abort_unless(\App\Support\RecordVisibility::apply(Rfq::query(),Auth::user())->whereKey($rfq->id)->exists(),403);
            }
            $budget = DepartmentBudget::active()->findOrFail($rfq?->budget_id ?: $data['budget_id']);
            $rfq ??= Rfq::create(['rfq_number'=>PaymentNumber::nextRfq(),'budget_id'=>$budget->id,'title'=>$budget->activity_title,'created_by'=>Auth::id(),'status'=>'draft']);
            $rfq->update(['title'=>$rfq->activityForm?->activity_name ?? $budget->activity_title,'notes'=>$data['notes']??null,'deadline'=>$data['deadline']??null]);
            $seenSources=[];
            foreach ($data['items'] as $index=>$input) {
                $source = !empty($input['source_line_item_id']) ? $rfq->activityForm?->lineItems()->find($input['source_line_item_id']) : null;
                if (!empty($input['source_line_item_id']) && !$source) $this->fail($index,'Invalid approved activity item.');
                if ($source && in_array($source->id,$seenSources,true)) $this->fail($index,'An activity item can only be used once.');
                if ($source) $seenSources[]=$source->id;
                $exempt = !empty($input['quotation_not_required']);
                $storeItem = !empty($input['available_in_store']);
                $catalog = !empty($input['vendor_rate_id']) ? VendorRate::available()->find($input['vendor_rate_id']) : null;
                if (!empty($input['vendor_rate_id']) && !$catalog) $this->fail($index,'The approved vendor rate has expired or is unavailable.');
                if ($exempt && $catalog) $this->fail($index,'Choose either direct payment or an approved vendor rate.');
                if ($exempt && (!$source || !$rfq->activityForm?->isApproved())) $this->fail($index,'Quotation Not Required needs an approved activity item.');
                if ($exempt && $storeItem) $this->fail($index,'An item sent directly to Payment cannot also be marked Available in Store.');
                if ($storeItem && (!$source || !$rfq->activityForm?->isApproved())) $this->fail($index,'Available in Store needs an approved activity item.');
                $skipsVendor = $exempt || $storeItem;
                $item = $rfq->requestItems()->create([
                    'source_line_item_id'=>$source?->id,'description'=>$skipsVendor ? $source->item_name : ($catalog?->item_name ?? $input['description']),
                    'quantity'=>$skipsVendor ? $source->quantity : $input['quantity'],'unit'=>$skipsVendor ? $source->unit : ($catalog?->unit ?? $input['unit']??null),
                    'request_remarks'=>trim(($input['request_remarks']??'').($catalog ? "\nApproved catalogue rate #".$catalog->id.' valid '.$catalog->valid_from->format('Y-m-d').' to '.$catalog->valid_until->format('Y-m-d') : '')),'quotation_not_required'=>$exempt,
                    'available_in_store'=>$storeItem,
                    'vendor_rate_id'=>$catalog?->id,'approved_rate'=>$catalog?->unit_rate,
                ]);
                if ($exempt) {
                    $amount=round((float)$source->quantity*(float)$source->rate,2);
                    if ($amount<=0) $this->fail($index,'The approved activity item needs a positive amount for direct payment.');
                    $payment=Payment::create([
                        'payment_number'=>PaymentNumber::next(),'activity_form_id'=>$rfq->activity_form_id,'created_by'=>Auth::id(),
                        'source'=>'direct_activity_form','payment_type'=>'full','amount_due'=>$amount,'net_amount'=>$amount,'po_total'=>0,
                        'scheduled_date'=>today(),'schedule_month'=>today()->startOfMonth(),'schedule_week'=>(int)ceil(today()->day/7),
                        'payment_method'=>'pending_finance','status'=>'pending_finance','activity_name'=>$rfq->title,
                        'activity_reference'=>$rfq->activityForm->form_number,'notes'=>'Quotation not required: '.$item->description.' · '.$rfq->rfq_number,
                    ]);
                    $item->update(['payment_id'=>$payment->id]);
                    continue;
                }
                if ($storeItem) {
                    // Already available in campus store — no vendor/PO involved.
                    // Tracked on the Store Action list until issued.
                    continue;
                }
                $vendors = $catalog ? collect([$catalog->vendor]) : Vendor::active()->whereIn('id',$input['vendor_ids']??[])->whereNotNull('email')->get();
                if ($vendors->isEmpty() || (!$catalog && $vendors->count() !== count(array_unique($input['vendor_ids']??[])))) $this->fail($index,'Choose active vendors with email addresses, or a valid approved rate.');
                foreach ($vendors as $vendor) {
                    $quote=$rfq->quotes()->firstOrCreate(['vendor_id'=>$vendor->id,'entry_method'=>$catalog?'catalogue':'portal'],['status'=>$catalog?'submitted':'invited','entered_by'=>Auth::id()]);
                    if (!$catalog && !$quote->vendor_token) { $quote->generateToken(); $quote->save(); }
                    RfqQuoteItem::create(['rfq_quote_id'=>$quote->id,'rfq_item_id'=>$item->id,'line_item_id'=>$source?->id,
                        'description'=>$item->description,'quantity'=>$item->quantity,'unit'=>$item->unit,'request_remarks'=>$item->request_remarks,
                        'unit_rate'=>$catalog?->unit_rate??0]);
                    if ($catalog) { $total=$quote->items()->sum('total'); $quote->update(['total_quoted'=>$total,'grand_total'=>$total,'submitted_at'=>now(),'notes'=>'Approved catalogue rate. Validity and price are recorded on the RFQ items.']); }
                }
            }
            if ($rfq->handoff_activity_id && $rfq->activityForm->lineItems()->whereNotIn('id',$seenSources)->exists()) $this->fail(0,'Include every approved activity item before completing RFQ preparation.');
            if ($rfq->quotes()->doesntExist()) $rfq->update(['status'=>'closed']);
            AuditLog::record(Auth::user(),'rfq.prepared',$rfq,$rfq->rfq_number);
            return $rfq;
        });
    }
    private function fail(int $index,string $message): never { throw ValidationException::withMessages(['items.'.$index=>$message]); }
}
