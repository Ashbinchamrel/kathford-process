<?php
require __DIR__.'/planning_governance_smoke.php';
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Models\{AuditLog,Role,User};
use App\Services\Planning\Governance;
use Illuminate\Support\Facades\Auth;

foreach(['board'=>$board,'cmt'=>$cmt,'department'=>$head] as $level=>$actor){
 Auth::setUser($actor);
 $view=$controller->overview(requestFor($actor,[],'/planning/overview','GET'));
 check($view->name()==='planning.governance-dashboard' && $view->getData()['level']===$level,"Overview chooses $level dashboard from membership");
 $html=$view->render();check(!str_contains($html,'/planning/dashboards/'),'dashboard menu links removed');
}
Auth::setUser($user);
check($controller->overview(requestFor($user))->name()==='planning.overview','unassigned Super Admin receives personal Overview');
denied(fn()=>$governanceController->dashboard(requestFor($user),'board'),'Super Admin without Board membership cannot open Board dashboard');
$board->update(['is_cmt_member'=>true]);
check(array_keys(Governance::dashboardLevels($board))===['board','cmt'],'dual membership exposes only own dashboard choices');
check($controller->overview(requestFor($board,['dashboard'=>'cmt']))->getData()['level']==='cmt','dual member can choose CMT within Overview');
denied(fn()=>$controller->overview(requestFor($head,['dashboard'=>'board'])),'Overview rejects an unauthorized dashboard query');
$board->update(['is_board_member'=>false,'is_cmt_member'=>false]);
check($controller->overview(requestFor($board))->name()==='planning.overview','removing membership removes specialized Overview');
denied(fn()=>$governanceController->dashboard(requestFor($board),'board'),'old Board dashboard URL is protected after membership removal');

$usersController=app(UserController::class);
Auth::setUser($user);
$update=['name'=>$board->name,'roles'=>[$board->role_id],'department_id'=>$dept->id,'is_active'=>1,'is_board_member'=>1,'is_cmt_member'=>1];
$usersController->update(requestFor($user,$update),$board);$board->refresh();
check($board->is_board_member && $board->is_cmt_member,'User settings checkboxes save both memberships');
$update['is_board_member']=0;$update['is_cmt_member']=0;
$usersController->update(requestFor($user,$update),$board);$board->refresh();
check(!$board->is_board_member && !$board->is_cmt_member,'unchecked User settings remove memberships');
$audit=AuditLog::where('action','user.updated')->orderByDesc('id')->first();
check($audit->new_values['is_board_member']===false && $audit->old_values['is_board_member']===true,'membership changes recorded in User audit history');
$board->update(['is_board_member'=>true]);unset($update['is_board_member'],$update['is_cmt_member']);
$usersController->update(requestFor($user,$update),$board);$board->refresh();
check($board->is_board_member,'older user forms without membership fields preserve membership');

$ownForm=$usersController->edit($user)->render();
check(str_contains($ownForm,'name="is_active" value="1"') && str_contains($ownForm,'name="is_board_member"'),'own User settings include active state and Board checkbox');
$createForm=$usersController->create()->render();
check(str_contains($createForm,'name="is_cmt_member"'),'new User form includes CMT checkbox');
$domain=config('kathford.allowed_email_domain','kathford.edu.np');
$usersController->store(requestFor($user,['name'=>'New CMT Member','email'=>'planning-membership-test@'.$domain,'roles'=>[$staffRole->id],'department_id'=>$dept->id,'password'=>'Test-only-password123','password_confirmation'=>'Test-only-password123','is_cmt_member'=>1,'is_board_member'=>0]));
$newMember=User::where('email','planning-membership-test@'.$domain)->firstOrFail();
check($newMember->is_cmt_member && !$newMember->is_board_member,'new User membership is saved');
$settings=$controller->setup(requestFor($user))->render();
check(!str_contains($settings,'Board, CMT and Department governance') && !str_contains($settings,'name="heads[') && !str_contains($settings,'name="board['),'Planning Setup has no duplicated membership or head controls');
check(str_contains($settings,'Default approval chains'),'Planning Setup keeps approval defaults');
denied(fn()=>$governanceController->save(requestFor($user,['board'=>[$head->id],'chains'=>['strategy'=>$chain->id]])),'old Planning settings endpoint cannot assign Board membership',422);
$departmentAdmin=app(DepartmentController::class);
$departmentAdmin->update(requestFor($user,['name'=>$dept->name,'head_user_id'=>$newMember->id,'is_active'=>1]),$dept);
check(in_array('department',array_keys(Governance::dashboardLevels($newMember))),'Department settings head assignment drives Overview');
check(!isset(Governance::dashboardLevels($head)['department']),'former head loses department dashboard access');
$departmentAdmin->update(requestFor($user,['name'=>$dept->name,'head_user_id'=>$head->id,'is_active'=>1]),$dept);

// Exercise the data migration in both directions inside this disposable database.
$migration=require __DIR__.'/../database/migrations/2026_09_14_000001_move_planning_membership_to_users.php';
$migration->down();
$legacy=\App\Models\Setting::get('planning_governance');
check(in_array($board->id,$legacy['board']) && in_array($newMember->id,$legacy['cmt']),'membership migration rollback preserves current selections');
$migration->up();
check($board->fresh()->is_board_member && $newMember->fresh()->is_cmt_member,'existing membership selections migrate to User checkboxes');
check(Governance::defaultChain('strategy')===$chain->id,'membership migration preserves approval defaults');
check(!array_key_exists('board',Governance::settings()),'old membership lists removed after migration');
echo "$checks total checks passed in isolated database.\n";

if(in_array('--preview',$argv,true)){
 @mkdir('/private/tmp/planning-governance-preview',0777,true);
 Auth::setUser($board->fresh());
 file_put_contents('/private/tmp/planning-governance-preview/overview.html',$controller->overview(requestFor($board->fresh(),[],'/planning/overview','GET'))->render());
 Auth::setUser($user);
 file_put_contents('/private/tmp/planning-governance-preview/setup.html',$controller->setup(requestFor($user))->render());
 file_put_contents('/private/tmp/planning-governance-preview/user.html',$usersController->edit($board->fresh())->render());
}
