<?php

require __DIR__.'/planning_overview_membership_smoke.php';

use App\Http\Controllers\Admin\PermissionController;
use App\Models\ActivityForm;
use App\Models\ApprovalChain;
use App\Models\FormCategory;
use App\Models\Notification;
use App\Models\PaymentAuthorisation;
use App\Models\Planning\Document;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Vendor;
use App\Services\ApprovalService;
use App\Services\Planning\Access;
use App\Services\Planning\CmtGoals;
use App\Support\ChainAccess;
use App\Support\FiscalYearContext;
use App\Support\RecordVisibility;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

Mail::fake();
Queue::fake();
$reviewChain = ApprovalChain::create(['name' => 'Assigned review only', 'is_active' => true]);
$reviewChain->syncVerifiers([$outsider->id]);
$reviewChain->syncApprovers([$newMember->id]);
Setting::set('planning_governance', ['chains' => ['strategy' => $chain->id, 'cmt_goals' => $reviewChain->id]], 'json', 'planning');
$src = $revision->items()->firstOrFail();
$group = ['key' => (string) Str::uuid(), 'title' => 'Canvas rollout', 'source_id' => $src->id, 'owner_id' => $cmt->id, 'due_on' => $period->ends_on, 'definition_of_done' => "AGS uploaded\nCourses live", 'deliverables' => 'Course templates', 'milestones' => 'April: templates; September: audit', 'proposed_tasks' => 'Prepare templates', 'supporting_owner_ids' => [$head->id], 'additional_source_ids' => [$src->id], 'results' => [['title' => 'AGS completion', 'metric' => '%', 'target' => 100, 'baseline' => 0], ['title' => 'Canvas adoption', 'metric' => '%', 'target' => 80, 'baseline' => 10]]];
$input = ['type' => 'cmt_goals', 'title' => 'CMT matrix test', 'parent_id' => $revision->id, 'period_id' => $period->id, 'approval_chain_id' => $reviewChain->id, 'cmt_groups' => [$group]];
Auth::setUser($cmt);
$controller->store(requestFor($cmt, $input));
$doc = Document::where('title', 'CMT matrix test')->firstOrFail();
check($doc->items()->count() === 2, 'one goal stores two separately trackable key results');
check(count(CmtGoals::groups($doc->items)) === 1, 'key results reopen under the same goal');
check($doc->items->first()->details['milestones'] === $group['milestones'], 'milestones preserved');
check(str_contains($controller->edit(requestFor($cmt), $doc)->render(), 'cmt-form'), 'CMT table editor renders saved draft');
$bad = $input;
$bad['title'] = 'Invalid source';
$bad['cmt_groups'][0]['additional_source_ids'] = [$cmtGoal->items()->first()->id];
denied(fn () => $controller->store(requestFor($cmt, $bad)), 'additional strategic links cannot target unrelated documents', 422);
check(! Document::where('title', 'Invalid source')->exists(), 'invalid links roll back draft creation');
$doc = $workflow->action($doc, $cmt, 'submit', $doc->version);
check($doc->status === 'pending_verification', 'CMT submit requires no verifier permission switches');
check($outsider->can('planning.view') && $outsider->can('planning.cmt_goals_view'), 'assigned reviewer receives Planning entry and document viewing');
check(Access::documents(Document::query(), $outsider)->whereKey($doc->id)->exists(), 'reviewer can find assigned CMT document');
Access::document($revision, $outsider);
check(true, 'reviewer can read linked strategic evidence');
denied(fn () => Access::document($cmtGoal, $outsider), 'reviewer cannot read unrelated approved CMT document');
check(! $outsider->can('planning.cmt_goals_create'), 'assignment grants no drafting permission');
denied(fn () => $workflow->action($doc, $newMember, 'accept', $doc->version), 'approver cannot skip verification');
$doc = $workflow->action($doc, $outsider, 'accept', $doc->version);
check($doc->status === 'pending_approval', 'assigned verifier completes step without permission switch');
$doc = $workflow->action($doc, $newMember, 'accept', $doc->version);
check($doc->status === 'approved', 'assigned approver completes step without permission switch');
Auth::setUser($cmt);
check(str_contains($controller->show(requestFor($cmt), $doc)->render(), 'Canvas adoption'), 'CMT review shows each key result');
$kr = $doc->items()->where('title', 'Canvas adoption')->firstOrFail();
$controller->checkin(requestFor($cmt, ['actual' => 50, 'evidence' => 'Canvas audit']), $doc, $kr);
check((float) $kr->fresh()->actual === 50.0 && $doc->items()->where('title', 'AGS completion')->first()->actual === null, 'progress updates only the selected result');

$vendor = Vendor::create(['contact_person' => 'Test', 'mobile_number' => '000', 'address' => 'Test', 'created_by' => $user->id, 'name' => 'Test vendor', 'category' => 'goods', 'email' => 'vendor@example.invalid', 'is_active' => true]);
$category = FormCategory::create(['name' => 'Test category', 'code' => 'TST', 'group' => 'activity', 'is_active' => true]);
$ctx = app(FiscalYearContext::class);
$ctx->year = $year;
$ctx->activeId = $year->id;
$ctx->enabled = true;
$records = [
    'activity_forms' => ActivityForm::create(['form_number' => 'TEST-A', 'category_id' => $category->id, 'creator_id' => $user->id, 'department_id' => $dept->id, 'activity_name' => 'Approval test', 'deadline_date' => '2026-09-30', 'status' => 'pending_verification', 'approval_chain_id' => $reviewChain->id]),
    'purchase_orders' => PurchaseOrder::create(['po_number' => 'TEST-PO', 'vendor_id' => $vendor->id, 'generated_by' => $user->id, 'status' => 'pending_verification', 'approval_chain_id' => $reviewChain->id]),
    'payment_authorisations' => PaymentAuthorisation::create(['authorisation_number' => 'TEST-PA', 'schedule_month' => '2026-09-01', 'schedule_week' => 1, 'created_by' => $user->id, 'status' => 'pending_verification', 'approval_chain_id' => $reviewChain->id]),
];
$approval = app(ApprovalService::class);
foreach ($records as $module => $record) {
    check($outsider->can($module.'.view') && $outsider->can($module.'.verify'), $module.' grants assigned review access');
    check(! ChainAccess::explicit($outsider, $module.'.view'), $module.' does not grant general queue access');
    check(RecordVisibility::apply($record->newQuery(), $outsider)->whereKey($record->id)->exists(), $module.' assigned record visible');
    $other = $record->replicate();
    $other->approval_chain_id = $chain->id;
    $number = ['activity_forms' => 'form_number', 'purchase_orders' => 'po_number', 'payment_authorisations' => 'authorisation_number'][$module];
    $other->$number .= '-OTHER';
    $other->save();
    check(! RecordVisibility::apply($record->newQuery(), $outsider)->whereKey($other->id)->exists(), $module.' unrelated record hidden');
    check(! $approval->canVerify($record, $newMember), $module.' future approver cannot verify');
    $approval->verify($record, $outsider, 'approved', null);
    $record->refresh();
    check($record->status === 'pending_approval' && $newMember->can($module.'.approve'), $module.' advances to assigned approval');
    $approval->approve($record, $newMember, 'approved', null);
    $record->refresh();
    check($record->status === 'approved', $module.' approval succeeds without switches');
}
Auth::setUser($user);
$permissionView = app(PermissionController::class)->edit($cmt)->render();
check(! str_contains($permissionView, '>Verify CMT Goals<') && str_contains($permissionView, 'Approval Chains'), 'permission matrix omits chain decision toggles');
if (in_array('--ui', $argv, true)) {
    @mkdir('/private/tmp/cmt-ui', 0777, true);
    file_put_contents('/private/tmp/cmt-ui/editor.html', $controller->create(requestFor($user, ['type' => 'cmt_goals']))->render());
    file_put_contents('/private/tmp/cmt-ui/permissions.html', $permissionView);
    copy(__DIR__.'/../public/js/cmt-goals.js', '/private/tmp/cmt-ui/cmt-goals.js');
}
echo "$checks total checks passed in isolated database.\n";

check(Notification::where('type', 'cmt_support_assignment')->where('user_id', $head->id)->count() === 1, 'checked supporting owner receives one notification for multiple key results');
check(Notification::where('type', 'cmt_support_assignment')->where('user_id', '!=', $head->id)->count() === 0, 'unchecked supporting owners receive no support notification');
$newInput = $input;
$newInput['title'] = 'Semester timeline selection';
$newInput['semester_index'] = 1;
$newInput['approval_chain_id'] = $chain->id;
$newInput['cmt_groups'][0]['due_on'] = '2027-03-31';
$newInput['cmt_groups'][0]['supporting_owner_ids'] = [];
unset($newInput['period_id']);
$controller->store(requestFor($user, $newInput));
$timelineDoc = Document::where('title', 'Semester timeline selection')->firstOrFail();
check($timelineDoc->period->starts_on === $revision->strategy_data['semesters'][1]['starts_on'], 'semester comes from Strategic Plan timeline without a Setup period');
check($timelineDoc->approval_chain_id === $reviewChain->id, 'form setting overrides client-supplied approval chain');
check(empty($timelineDoc->items->first()->details['supporting_owner_ids']), 'unchecking all owners saves empty selection');
$bad = $newInput;
$bad['semester_index'] = 99;
denied(fn () => $controller->store(requestFor($user, $bad)), 'invalid timeline semester rejected', 422);
$html = $controller->edit(requestFor($user), $timelineDoc)->render();
check(! str_contains($html, 'id="cmt-reference"') && ! str_contains($html,'name="approval_chain_id"'), 'CMT editor omits template and editable chain');
echo "$checks total rework checks passed.\n";
