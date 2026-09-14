<?php

require __DIR__.'/planning_overview_membership_smoke.php';

use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\Planning\Document;
use Illuminate\Support\Facades\Auth;

$testingChain = ApprovalChain::create(['name' => 'Admin testing chain', 'is_active' => true]);
$testingChain->syncVerifiers([$outsider->id]);
$testingChain->syncApprovers([$newMember->id]);
$outsider->update(['is_active' => false]);
$newMember->update(['is_active' => false]);
Auth::setUser($user);
$testingPayload = $payload;
$testingPayload['title'] = 'Admin testing strategy';
$testingPayload['approval_chain_id'] = $testingChain->id;
$controller->store(requestFor($user, $testingPayload));
$testing = Document::where('title', 'Admin testing strategy')->firstOrFail();
denied(fn () => $workflow->action($testing, $board->fresh(), 'submit', $testing->version), 'ordinary Board submit still requires active chain members', 422);
$testing = $workflow->action($testing, $user, 'submit', $testing->version);
check($testing->status === 'pending_verification' && $testing->step()['user_id'] === $outsider->id, 'admin submits with original verifier preserved');
$audit = AuditLog::where('model_id', $testing->id)->where('action', 'planning.submit')->firstOrFail();
check($audit->user_id === $user->id && $audit->new_values['super_admin_override'] && count($audit->new_values['chain_permission_exceptions']) === 2, 'admin submission exceptions are audited');
$html = $controller->show(requestFor($user), $testing)->render();
check(str_contains($html, 'You can perform this step as Super Admin') && str_contains($html, 'Verify'), 'admin sees verification control');
denied(fn () => $workflow->action($testing, $board->fresh(), 'accept', $testing->version), 'ordinary unassigned Board member cannot decide');
denied(fn () => $workflow->action($testing, $user, 'return', $testing->version), 'admin return still requires reason', 422);
$oldVersion = $testing->version;
$testing = $workflow->action($testing, $user, 'accept', $testing->version);
check($testing->status === 'pending_approval' && $testing->step()['user_id'] === $newMember->id, 'admin verification advances one step only');
denied(fn () => $workflow->action($testing, $user, 'accept', $oldVersion), 'admin stale decision rejected', 422);
$html = $controller->show(requestFor($user), $testing)->render();
check(str_contains($html, 'Approve'), 'admin sees approval control after verification');
$testing = $workflow->action($testing, $user, 'accept', $testing->version);
check($testing->status === 'approved', 'admin completes approval');
$audit = AuditLog::where('model_id', $testing->id)->where('action', 'planning.accept')->orderByDesc('id')->firstOrFail();
check($audit->user_id === $user->id && $audit->new_values['assigned_user_id'] === $newMember->id && $audit->new_values['step_action'] === 'approve', 'approval audit identifies actual actor and assigned approver');
check($testing->decisions()->where('actor_id', $user->id)->where('note', 'like', '%Super Admin performed%')->count() === 2, 'both override decisions visible in history');
check(! $outsider->fresh()->can('planning.strategy_verify') && ! $newMember->fresh()->can('planning.strategy_approve'), 'testing does not grant chain members permissions');
$emptyChain = ApprovalChain::create(['name' => 'Empty test chain', 'is_active' => true]);
$testingPayload['title'] = 'Invalid testing strategy';
$testingPayload['approval_chain_id'] = $emptyChain->id;
$controller->store(requestFor($user, $testingPayload));
$invalid = Document::where('title', 'Invalid testing strategy')->firstOrFail();
denied(fn () => $workflow->action($invalid, $user, 'submit', $invalid->version), 'admin must still select a chain containing an approver', 422);
echo "$checks total checks passed in isolated database.\n";
