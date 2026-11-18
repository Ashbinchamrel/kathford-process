<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{ActivityForm,PurchaseOrder,PaymentAuthorisation,ApprovalAction,User};
foreach ([ActivityForm::class,PurchaseOrder::class,PaymentAuthorisation::class] as $class) {
    $record=new $class(['status'=>'approved']);
    $first=new ApprovalAction(['decision'=>'submitted','layer'=>1,'acted_at'=>'2026-09-19 09:00:00']);
    $first->setRelation('actor',new User(['name'=>'Submitter']));
    $last=new ApprovalAction(['decision'=>'approved','layer'=>200,'acted_at'=>'2026-09-20 10:00:00','note'=>'<script>unsafe</script>']);
    $last->setRelation('actor',new User(['name'=>'Approver']));
    $record->setRelation('approvalChain',null)->setRelation('approvalActions',collect([$first,$last]));
    $html=view('partials.record-approval',['approvalRecord'=>$record,'approvalLabel'=>'Test approval'])->render();
    if (!str_contains($html,'Submitter') || !str_contains($html,'Approver') || !str_contains($html,'Time since previous action') || str_contains($html,'<script>unsafe')) throw new RuntimeException('Timeline regression: '.$class);
    echo "PASS $class history, timing and escaped comments\n";
}
$html=view('partials.record-approval',['approvalRecord'=>null,'approvalLabel'=>'No linked chain'])->render();
if (!str_contains($html,'no linked approval history')) throw new RuntimeException('Missing empty state');
echo "PASS unlinked record empty state\n";
