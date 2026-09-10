<?php
// Explicit local integration check. All changes roll back; email and queues are faked.
if (($argv[1] ?? '') !== '--local') { fwrite(STDERR,"Run with --local against the configured development database.\n");exit(1); }
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver'=>'array']);
Illuminate\Support\Facades\Queue::fake();
Illuminate\Support\Facades\Mail::fake();
use Illuminate\Support\Facades\DB;
use App\Models\{ActivityForm,DepartmentBudget,FormLineItem,Rfq,Vendor,VendorRate,Payment,User,Setting,ProcurementChecklist};
use App\Support\FiscalYearContext;
function check($condition,$message) { if(!$condition) throw new RuntimeException($message); echo "PASS: {$message}\n"; }
DB::beginTransaction();
try {
    $user=User::whereHas('role',fn($r)=>$r->where('name','super_admin'))->firstOrFail();
    Illuminate\Support\Facades\Auth::setUser($user);
    $context=app(FiscalYearContext::class);$context->year=App\Models\FiscalYear::findOrFail(Setting::get('active_fiscal_year_id'));$context->activeId=$context->year->id;$context->enabled=true;
    $source=ActivityForm::whereHas('category')->whereHas('budget')->firstOrFail();
    $activity=ActivityForm::create(['form_number'=>'TEST-'.bin2hex(random_bytes(4)),'category_id'=>$source->category_id,'department_id'=>$source->department_id,'budget_id'=>$source->budget_id,'creator_id'=>$user->id,'activity_name'=>'Integration test activity','deadline_date'=>today()->addDay(),'status'=>'draft','total_estimated_amount'=>250]);
    $line=$activity->lineItems()->create(['item_name'=>'Integration test item','quantity'=>2,'unit'=>'pcs','rate'=>125]);
    $approval=app(App\Services\ApprovalService::class);
    $approval->submit($activity,$user);$activity->refresh();
    $verifier=$approval->currentVerifier($activity);$approval->verify($activity,$verifier,'modified_approved','Please revise');$activity->refresh();
    check($activity->status==='draft' && $activity->isEditable(),'Modify returns the activity for editing');
    $activity->update(['remarks'=>'Revised description']);
    $approval->submit($activity,$user);$activity->refresh();
    for($i=0;$activity->status==='pending_verification' && $i<20;$i++){ $approval->verify($activity,$approval->currentVerifier($activity),'approved',null);$activity->refresh(); }
    for($i=0;$activity->status==='pending_approval' && $i<20;$i++){ $approval->approve($activity,$approval->currentApprover($activity),'approved',null);$activity->refresh(); }
    check($activity->status==='approved','All approval layers complete in order');
    $rfq=Rfq::where('handoff_activity_id',$activity->id)->firstOrFail();
    $again=app(App\Services\ActivityRfqService::class)->handoff($activity);
    check($again->id===$rfq->id && Rfq::where('handoff_activity_id',$activity->id)->count()===1,'Final approval creates one RFQ preparation entry');
    $bypassCategory = $activity->category->replicate();
    $bypassCategory->code = 'T'.bin2hex(random_bytes(4));
    $bypassCategory->bypasses_procurement_to_payment = true;
    $bypassCategory->save();
    $direct = ActivityForm::create(['form_number'=>'TEST-'.bin2hex(random_bytes(4)), 'category_id'=>$bypassCategory->id, 'department_id'=>$source->department_id, 'budget_id'=>$source->budget_id, 'creator_id'=>$user->id, 'activity_name'=>'Bypass test', 'deadline_date'=>today()->addDay(), 'status'=>'draft', 'total_estimated_amount'=>250]);
    $direct->lineItems()->create(['item_name'=>'Direct expense','quantity'=>2,'unit'=>'pcs','rate'=>125]);
    $approval->submit($direct,$user); $direct->refresh();
    check(!Payment::where('activity_form_id',$direct->id)->exists(), 'Bypass does not create payment before final approval');
    for($i=0;$direct->status==='pending_verification' && $i<20;$i++){ $approval->verify($direct,$approval->currentVerifier($direct),'approved',null);$direct->refresh(); }
    for($i=0;$direct->status==='pending_approval' && $i<20;$i++){ $approval->approve($direct,$approval->currentApprover($direct),'approved',null);$direct->refresh(); }
    $directPayment = Payment::where('activity_form_id',$direct->id)->firstOrFail();
    check($direct->isApproved() && $directPayment->status==='pending_finance' && (float)$directPayment->amount_due===250.0, 'Bypass forwards approved amount to Accounts scheduling');
    check(!Rfq::where('activity_form_id',$direct->id)->exists() && !$directPayment->purchase_order_id, 'Bypass skips procurement documents');
    app(App\Services\ActivityPaymentService::class)->handoff($direct);
    check(Payment::where('activity_form_id',$direct->id)->count()===1, 'Repeated bypass handoff creates no duplicate payment');
    $service=app(App\Services\RfqPreparationService::class);
    $data=['rfq_id'=>$rfq->id,'budget_id'=>$activity->budget_id,'items'=>[['source_line_item_id'=>$line->id,'description'=>'Tampered display','quantity'=>999,'unit'=>'pcs','quotation_not_required'=>true]]];
    $preparationView = app(App\Http\Controllers\RfqController::class)->create(Illuminate\Http\Request::create('/rfq/create', 'GET', ['rfq_id'=>$rfq->id]))->getData();
    check($preparationView['selectedActivity']->activity_name === $activity->activity_name, 'RFQ preparation receives the linked activity subject');
    $service->save($data);
    check($rfq->fresh()->title === $activity->activity_name, 'RFQ preparation preserves the linked activity title');
    $item=$rfq->requestItems()->firstOrFail();$payment=Payment::findOrFail($item->payment_id);
    check((float)$payment->amount_due===250.0 && $payment->activity_form_id===$activity->id,'Direct payment uses the approved item amount and activity reference');
    try{$service->save($data);throw new RuntimeException('Duplicate preparation accepted');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){check($e->getStatusCode()===422,'Duplicate preparation is blocked');}
    $vendor=Vendor::active()->whereNotNull('email')->firstOrFail();
    $standalone=$service->save(['budget_id'=>$activity->budget_id,'items'=>[['description'=>'Standalone item','quantity'=>1,'unit'=>'pcs','vendor_ids'=>[$vendor->id]]]]);
    check(!$standalone->activity_form_id && $standalone->budget_id===$activity->budget_id,'Standalone RFQ uses budget without requiring activity');
    $rate=VendorRate::create(['vendor_id'=>$vendor->id,'item_name'=>'Contract item','unit'=>'pcs','unit_rate'=>50,'valid_from'=>today()->subDay(),'valid_until'=>today()->addDay(),'is_active'=>true,'approved_by'=>$user->id]);
    $catalogue=$service->save(['budget_id'=>$activity->budget_id,'items'=>[['description'=>'Contract item','quantity'=>3,'unit'=>'pcs','vendor_rate_id'=>$rate->id]]]);
    check((float)$catalogue->quotes()->firstOrFail()->grand_total===150.0,'Valid catalogue rate supplies quote values without requesting a new quote');
    $rate->update(['valid_until'=>today()->subDay()]);
    try{$service->save(['budget_id'=>$activity->budget_id,'items'=>[['description'=>'Contract item','quantity'=>3,'vendor_rate_id'=>$rate->id]]]);throw new RuntimeException('Expired rate accepted');}catch(Illuminate\Validation\ValidationException $e){echo "PASS: Expired catalogue rate rejected\n";}
    $checklist=new ProcurementChecklist(['question_snapshot'=>['q_1'=>['label'=>'Required test','required'=>true],'q_2'=>['label'=>'Optional test','required'=>false]],'answers'=>['q_1'=>true]]);
    check($checklist->controlsComplete(),'Checklist completion respects required and optional questions');
    $checklist->answers=['q_2'=>true];check(!$checklist->controlsComplete(),'Missing required control blocks completion');

    $po=App\Models\PurchaseOrder::create(['po_number'=>'TEST-'.bin2hex(random_bytes(4)),'rfq_quote_id'=>$standalone->quotes()->first()->id,'vendor_id'=>$vendor->id,'generated_by'=>$user->id,'subtotal'=>100,'total_amount'=>100,'status'=>'fully_received']);
    $poItem=$po->items()->create(['description'=>'Return test','quantity'=>2,'unit'=>'pcs','unit_rate'=>50,'total'=>100]);
    $bill=App\Models\VendorBill::create(['purchase_order_id'=>$po->id,'vendor_id'=>$vendor->id,'bill_number'=>'TEST-RETURN','disk_path'=>'test/unused.pdf','original_name'=>'test.pdf','mime_type'=>'application/pdf','file_size'=>0,'bill_date'=>today(),'amount'=>100,'status'=>'submitted','submitted_at'=>now()]);
    $bill->items()->create(['purchase_order_item_id'=>$poItem->id,'quantity'=>2,'unit_rate'=>50,'total'=>100]);
    $controls=ProcurementChecklist::create(['purchase_order_id'=>$po->id,'vendor_bill_id'=>$bill->id,'vendor_id'=>$vendor->id,'fulfillment_type'=>'goods','status'=>'pending_controls']);
    check(count($controls->question_snapshot)>0,'New checklists snapshot configured controls');
    $payable=Payment::create(['payment_number'=>App\Support\PaymentNumber::next(),'vendor_bill_id'=>$bill->id,'purchase_order_id'=>$po->id,'vendor_id'=>$vendor->id,'created_by'=>$user->id,'source'=>'checklist','payment_type'=>'full','po_total'=>100,'amount_due'=>100,'net_amount'=>100,'scheduled_date'=>today(),'payment_method'=>'pending_finance','status'=>'pending_finance']);
    $scheduled=Payment::create(['payment_number'=>App\Support\PaymentNumber::next(),'parent_payment_id'=>$payable->id,'created_by'=>$user->id,'source'=>'checklist','payment_type'=>'partial','po_total'=>100,'amount_due'=>100,'net_amount'=>100,'scheduled_date'=>today(),'payment_method'=>'pending_finance','status'=>'paid','amount_paid'=>100]);
    $request=Illuminate\Http\Request::create('/','POST',['reason'=>'Items do not match specification','return_type'=>'goods_and_invoice']);
    $controller=app(App\Http\Controllers\ProcurementChecklistController::class);
    try{$controller->returnToVendor($request,$controls);throw new RuntimeException('Paid child invoice returned');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){check($e->getStatusCode()===422,'Paid child schedule blocks invoice return');}
    $scheduled->update(['status'=>'scheduled','amount_paid'=>0]);
    $controller->returnToVendor($request,$controls);
    check($payable->fresh()->status==='cancelled' && $scheduled->fresh()->status==='cancelled','Invoice return cancels parent and child schedules');
    check($poItem->invoicedQuantity()===0.0,'Returned quantities are available for a corrected invoice');
    check($controls->fresh()->status==='returned' && $controls->returns()->exists(),'Return reason is retained for vendor notification');
    try{$payable->refresh()->update(['status'=>'scheduled']);throw new RuntimeException('Returned bill rescheduled');}catch(Illuminate\Validation\ValidationException $e){echo "PASS: Returned invoice cannot be rescheduled\n";}
    try{$controller->sendToAccounts(Illuminate\Http\Request::create('/','POST'),$controls->fresh());throw new RuntimeException('Returned checklist sent again');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){check($e->getStatusCode()===422,'Returned checklist cannot be sent to Accounts again');}
    Illuminate\Support\Facades\Queue::assertPushed(App\Jobs\SendProcurementReturnEmail::class);
    $extraVendor=$vendor->replicate();$extraVendor->email='integration-test@example.invalid';$extraVendor->save();
    app(App\Http\Controllers\RfqController::class)->inviteAdditional(Illuminate\Http\Request::create('/','POST',['vendor_ids'=>[$extraVendor->id]]),$standalone);
    $invitation=$standalone->quotes()->where('vendor_id',$extraVendor->id)->firstOrFail();
    check($invitation->status==='invited' && (float)$invitation->items()->firstOrFail()->unit_rate===0.0,'Additional vendor receives identical items without entered prices');
    check($invitation->items()->first()->rfq_item_id===$standalone->requestItems()->first()->id,'Additional quotes preserve item identity for comparison');
    Illuminate\Support\Facades\Mail::assertNothingSent();
    echo "PASS: No live email sent; rolling back all test records\n";
} catch(Throwable $e) { echo 'FAIL: '.get_class($e).' '.$e->getMessage()."\n";$failed=true; }
finally { while(DB::transactionLevel()>0) DB::rollBack(); }
exit(isset($failed)?1:0);
