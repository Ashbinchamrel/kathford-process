<?php

require __DIR__.'/cmt_chain_access_smoke.php';

use App\Models\Notification;
use App\Models\Planning\Document;
use App\Models\Setting;
use App\Models\User;
use App\Services\Planning\CmtGoals;
use Illuminate\Support\Facades\Auth;

Setting::set('planning_governance', ['chains' => ['strategy' => $chain->id, 'cmt_goals' => $reviewChain->id, 'goals' => $reviewChain->id]], 'json', 'planning');
$dg = $input;
$dg['type'] = 'goals';
$dg['title'] = 'Department goal parity';
$dg['department_id'] = $dept->id;
$dg['semester_index'] = 0;
$dg['cmt_groups'][0]['owner_id'] = $head->id;
$dg['cmt_groups'][0]['supporting_owner_ids'] = [$newMember->id];
Auth::setUser($head);
$controller->store(requestFor($head, $dg));
$department = Document::where('title', 'Department goal parity')->firstOrFail();
check($department->parent_id === $revision->id, 'Department Goals link directly to Strategic Plan');
check($department->period->starts_on === $revision->strategy_data['semesters'][0]['starts_on'], 'Department semester follows Strategic Plan timeline');
check(count(CmtGoals::groups($department->items)) === 1 && $department->items->count() === 2, 'Department Goals share grouped multiple key results');
$html = $controller->edit(requestFor($head), $department)->render();
check(str_contains($html, 'Department semester goal') && str_contains($html, 'Approved Strategic Plan'), 'Department editor uses shared table and correct labels');
$bad = $dg;
$bad['department_id'] = $otherDept->id;
denied(fn () => $controller->store(requestFor($head, $bad)), 'department user cannot prepare another department goals');
$bad = $dg;
$bad['parent_id'] = $doc->id;
denied(fn () => $controller->store(requestFor($head, $bad)), 'new Department Goals cannot link through CMT', 422);
$wrongOwner = User::create(['name' => 'Outside owner', 'email' => 'owner-other@example.invalid', 'password' => 'unused', 'role_id' => $staffRole->id, 'department_id' => $otherDept->id, 'is_active' => true]);
$bad = $dg;
$bad['cmt_groups'][0]['owner_id'] = $wrongOwner->id;
denied(fn () => $controller->store(requestFor($head, $bad)), 'accountable owner must belong to concerned department', 422);
$department = $workflow->action($department, $head, 'submit', $department->version);
$department = $workflow->action($department, $outsider, 'accept', $department->version);
$department = $workflow->action($department, $newMember, 'accept', $department->version);
check($department->status === 'approved', 'Department Goals follow configured chain');
check(Notification::where('type', 'department_support_assignment')->where('user_id', $newMember->id)->count() === 1, 'selected supporting owner notified once');
Auth::setUser($user);
$kr = $department->items->first();
$controller->checkin(requestFor($user, ['actual' => 42, 'evidence' => 'Department measurement']), $department, $kr);
check((float) $kr->fresh()->actual === 42.0, 'Department result records progress');
check(str_contains($controller->show(requestFor($user), $department)->render(), 'Department semester goal / linked goal'), 'Department review uses same grouped layout');
$contextDoc = $department->replicate();
$contextDoc->type = 'cmt_goals';
$contextDoc->title = 'Approved CMT reference';
$contextDoc->save();
foreach ($department->items as $item) {
    $copy = $item->replicate();
    $copy->document_id = $contextDoc->id;
    $copy->save();
}
$context = collect(CmtGoals::approvedContext([$revision->id]))->where('document_title', 'Approved CMT reference');
check($context->count() === 1 && count($context->first()['results']) === 2, 'CMT reference groups approved key results for department context');
check($context->first()['source_id'] === $department->items->first()->source_id && $context->first()['starts_on'] === $department->period->starts_on, 'CMT reference includes precise strategic row and semester dates');
$contextDoc->status = 'draft';
$contextDoc->save();
check(! collect(CmtGoals::approvedContext([$revision->id]))->contains('document_title', 'Approved CMT reference'), 'Draft CMT goals excluded from department context');
$contextDoc->status = 'approved';
$contextDoc->save();
check(CmtGoals::approvedContext([]) === [], 'CMT reference restricted to available Strategic Plans');
if (in_array('--ui', $argv, true)) {
    file_put_contents('/private/tmp/cmt-ui/department.html', $controller->create(requestFor($head, ['type' => 'goals']))->render());
}
echo "$checks total department parity checks passed.\n";
