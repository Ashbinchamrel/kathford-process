<?php
// All changes in this test use the isolated in-memory database from the workflow smoke test.
require __DIR__.'/planning_workflow_smoke.php';
use App\Services\Planning\{Governance,StrategyMatrix};
use App\Http\Controllers\Planning\{StrategyController,GovernanceController};
use App\Models\Planning\Period;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Validation\ValidationException;

function requestFor($user,array $data=[],string $path='/planning',string $method='POST'): Request {
 $r=Request::create($path,$method,$data);app()->instance('request',$r);$r->setUserResolver(fn()=>$user);return $r;
}
function denied(callable $fn,string $message,int $status=403): void {
 try{$fn();throw new RuntimeException('Unexpected success: '.$message);}catch(HttpException $e){check($e->getStatusCode()===$status,$message);}catch(ValidationException $e){check($status===422,$message);}
}
function approvePlanning($document,$actor,$workflow): void {$document->refresh();$workflow->action($document,$actor,'submit',$document->version);$document->refresh();$workflow->action($document,$actor,'accept',$document->version);$document->refresh();}

$strategyController=app(StrategyController::class);
$governanceController=app(GovernanceController::class);
$matrix=['starts_on'=>'2026-04-01','ends_on'=>'2028-03-31','semesters'=>[
 ['label'=>'April 2026–September 2026','starts_on'=>'2026-04-01','ends_on'=>'2026-09-30'],
 ['label'=>'October 2026–March 2027','starts_on'=>'2026-10-01','ends_on'=>'2027-03-31'],
 ['label'=>'April 2027–September 2027','starts_on'=>'2027-04-01','ends_on'=>'2027-09-30'],
 ['label'=>'October 2027–March 2028','starts_on'=>'2027-10-01','ends_on'=>'2028-03-31'],
]];
$matrixRows=[['priority'=>'Academic distinction','goal'=>'Institutionalize KLEM','strategy'=>'Pedagogical overlay','targets'=>["100% AGS Plan\n80% Canvas adoption",'90% Canvas adoption','100% Canvas adoption','']]];
$payload=['type'=>'strategy','title'=>'Strategic Priorities 2026–2028','approval_chain_id'=>$chain->id,'matrix'=>$matrix,'rows'=>$matrixRows];
$controller->store(requestFor($user,$payload));
$strategy=\App\Models\Planning\Document::where('type','strategy')->latest()->firstOrFail();
check($strategy->strategy_data['semesters'][3]['label']==='October 2027–March 2028','four-semester timeline persisted');
check($strategy->items->first()->details['strategy']['targets'][0]==="100% AGS Plan\n80% Canvas adoption",'multiline target KPI values preserved');
check(str_contains($controller->show(requestFor($user),$strategy)->render(),'Target KPIs and timeline'),'strategy matrix detail renders');
$invalid=$matrix;$invalid['semesters'][1]['starts_on']='2026-09-01';
denied(fn()=>StrategyMatrix::validate($invalid,$matrixRows),'overlapping semesters rejected',422);
$invalid=$matrix;$invalid['semesters'][3]['ends_on']='2028-04-01';
denied(fn()=>StrategyMatrix::validate($invalid,$matrixRows),'out-of-period semester rejected',422);

// Register gates against the isolated permission store created by the migrations.
foreach(\App\Models\Permission::pluck('key') as $key) \Illuminate\Support\Facades\Gate::define($key,fn($u)=>$u->permissions()->where('key',$key)->exists());
$staffRole=\App\Models\Role::firstOrCreate(['name'=>'governance_staff'],['display_name'=>'Governance Staff']);
$board=\App\Models\User::create(['name'=>'Board Editor','email'=>'board@example.invalid','password'=>'unused','role_id'=>$staffRole->id,'department_id'=>$dept->id,'is_active'=>true]);
$cmt=\App\Models\User::create(['name'=>'CMT User','email'=>'cmt@example.invalid','password'=>'unused','role_id'=>$staffRole->id,'department_id'=>$dept->id,'is_active'=>true]);
$head=\App\Models\User::create(['name'=>'Department Head','email'=>'head@example.invalid','password'=>'unused','role_id'=>$staffRole->id,'department_id'=>$dept->id,'is_active'=>true]);
$dept->update(['head_user_id'=>$head->id]);
$allPerms=\App\Models\Permission::where('module','planning')->whereNotIn('key',['planning.all_departments','planning.setup'])->pluck('id')->all();
foreach([$board,$cmt,$head] as $actor)$actor->permissions()->sync($allPerms);
$board->update(['is_board_member'=>true]);
$cmt->update(['is_cmt_member'=>true]);
check(Governance::canWrite($board,'strategy','edit'),'designated Board editor can edit');
denied(fn()=>$strategyController->editor(requestFor($cmt),$strategy),'CMT membership does not grant Board editing');
$board->permissions()->detach(\App\Models\Permission::where('key','planning.strategy_edit')->value('id'));
denied(fn()=>$strategyController->editor(requestFor($board),$strategy),'Board membership without edit permission cannot edit');
$board->permissions()->sync($allPerms);
$edited=$payload+['version'=>$strategy->version];$edited['title']='Board strategic priorities';
$controller->update(requestFor($board,$edited),$strategy);$strategy->refresh();
check($strategy->title==='Board strategic priorities','permitted editor saves strategy');
denied(fn()=>$controller->update(requestFor($board,$edited),$strategy),'stale strategic edit rejected',409);
approvePlanning($strategy,$user,$workflow);
denied(fn()=>$controller->update(requestFor($board,$payload+['version'=>$strategy->version]),$strategy),'approved strategy cannot be overwritten');

$period=Period::create(['name'=>'Semester 1','academic_year'=>'2083','starts_on'=>'2026-04-01','ends_on'=>'2026-09-30']);
\App\Models\Setting::set('planning_governance',['chains'=>['strategy'=>$chain->id,'cmt_goals'=>$chain->id]],'json','planning');
$goalData=['type'=>'cmt_goals','title'=>'CMT Semester 1 Goals','approval_chain_id'=>$chain->id,'period_id'=>$period->id,'parent_id'=>$strategy->id,'items'=>[['title'=>'Canvas adoption','source_id'=>$strategy->items->first()->id,'owner_id'=>$cmt->id,'definition_of_done'=>'Adoption report verified','metric'=>'Canvas adoption (%)','baseline'=>0,'target'=>80,'due_on'=>'2026-09-30']]];
$controller->store(requestFor($cmt,$goalData));$cmtGoal=\App\Models\Planning\Document::where('type','cmt_goals')->firstOrFail();
check($cmtGoal->department_id===null && $cmtGoal->parent_id===$strategy->id,'CMT goals link to Board strategy at college level');
denied(fn()=>$controller->store(requestFor($head,$goalData)),'Department membership cannot create CMT goals');
approvePlanning($cmtGoal,$user,$workflow);
$departmentGoalData=$goalData;$departmentGoalData['type']='goals';$departmentGoalData['title']='Department Semester 1 Goals';$departmentGoalData['department_id']=$dept->id;$departmentGoalData['parent_id']=$cmtGoal->id;$departmentGoalData['items'][0]['source_id']=$cmtGoal->items->first()->id;$departmentGoalData['items'][0]['owner_id']=$head->id;
$controller->store(requestFor($head,$departmentGoalData));$departmentGoal=\App\Models\Planning\Document::where('title','Department Semester 1 Goals')->firstOrFail();
approvePlanning($departmentGoal,$user,$workflow);
check($departmentGoal->parent_id===$cmtGoal->id,'Department goals link to approved CMT goals');
$wrong=$departmentGoalData;$wrong['parent_id']=$strategy->id;
denied(fn()=>$controller->store(requestFor($head,$wrong)),'Department goals cannot skip CMT layer',422);
$wrong=$departmentGoalData;$wrong['items'][0]['source_id']=$strategy->items->first()->id;
denied(fn()=>$controller->store(requestFor($head,$wrong)),'goal row cannot link to a different parent',422);

$review=['review_semester'=>0,'review_reason'=>'Semester one Board review'];
$controller->amend(requestFor($board,$review),$strategy);
$revision=\App\Models\Planning\Document::where('previous_id',$strategy->id)->firstOrFail();
check($strategy->fresh()->status==='approved' && $revision->status==='draft','current strategy stays approved during review');
$controller->amend(requestFor($board,$review),$strategy);
check(\App\Models\Planning\Document::where('previous_id',$strategy->id)->count()===1,'duplicate review reuses pending revision');
$revisionPayload=$payload+['version'=>$revision->version];$revisionPayload['rows'][0]['targets'][0]='85% Canvas adoption';
$controller->update(requestFor($board,$revisionPayload),$revision);$revision->refresh();
check($revision->strategy_data['review_reason']===$review['review_reason'],'review reason survives editing');
approvePlanning($revision,$user,$workflow);
check($strategy->fresh()->status==='superseded' && $revision->fresh()->status==='approved','approval publishes new strategy revision');
\App\Services\Planning\Access::document($strategy->fresh(),$cmt);
check(true,'CMT retains read access to the historical approved strategy');
check($cmtGoal->fresh()->parent_id===$strategy->id && $strategy->items->first()->details['strategy']['targets'][0]!==$revision->items->first()->details['strategy']['targets'][0],'existing CMT links retain original approved strategy and targets');
$wrong=$goalData;$wrong['title']='New goals on old strategy';
denied(fn()=>$controller->store(requestFor($cmt,$wrong)),'new goals cannot use superseded strategy',422);

foreach(['strategy','cmt_goals','goals'] as $type){\Illuminate\Support\Facades\Auth::setUser($user);check(strlen($controller->create(requestFor($user,['type'=>$type],'/planning/documents/create','GET'))->render())>1000,"$type editor renders after hierarchy changes");}
$controller->checkin(requestFor($head,['actual'=>50,'evidence'=>'Department Canvas adoption report']),$departmentGoal,$departmentGoal->items->first());
check($departmentGoal->items->first()->fresh()->actual==50,'Department progress reporting persists');
foreach(['board'=>$board,'cmt'=>$cmt,'department'=>$head] as $level=>$actor){
 \Illuminate\Support\Facades\Auth::setUser($actor);
 $view=$governanceController->dashboard(requestFor($actor,[],"/planning/dashboards/$level",'GET'),$level);
 check(strlen($view->render())>1000,"$level dashboard renders");
 check($view->getData()['summary']['documents']===1,"$level dashboard reports the correct goal tier");
}
denied(fn()=>$governanceController->dashboard(requestFor($head),'board'),'department head cannot open Board dashboard');
denied(fn()=>$governanceController->dashboard(requestFor($board),'department'),'Board member cannot open unassigned Department dashboard');
$otherDept=\App\Models\Department::create(['name'=>'Unrelated','code'=>'UNREL','is_active'=>true]);
denied(fn()=>$governanceController->dashboard(requestFor($head,['department_id'=>$otherDept->id]),'department'),'department head cannot switch into another department');
$governanceController->save(requestFor($user,['chains'=>['strategy'=>$chain->id,'cmt_goals'=>$chain->id]]));
check(Governance::defaultChain('strategy')===$chain->id,'default approval chains saved');
denied(fn()=>$governanceController->save(requestFor($user,['heads'=>[$otherDept->id=>$head->id]])),'Planning Setup cannot change department heads',422);
\Illuminate\Support\Facades\Auth::setUser($user);
check(strlen($controller->setup(requestFor($user))->render())>1000,'governance settings renders');
$reference=json_decode(file_get_contents(resource_path('data/kathford-strategy-reference.json')),true);
StrategyMatrix::validate($reference['matrix'],$reference['rows']);
check(count($reference['rows'])===36 && count(array_unique(array_column($reference['rows'],'priority')))===8,'all eight supplied priorities and 36 rows fit the strategy matrix');
$referenceView=$strategyController->editor(requestFor($user,['template'=>'reference'],'/planning/documents/create','GET'),new \App\Models\Planning\Document(['type'=>'strategy']));
check($referenceView->getData()['document']->title===$reference['title'],'supplied strategy opens as an unsaved draft');
$head->permissions()->detach(\App\Models\Permission::where('key','planning.goals_report')->value('id'));
denied(fn()=>$controller->checkin(requestFor($head,['actual'=>99,'evidence'=>'Denied update']),$departmentGoal,$departmentGoal->items->first()),'progress reporting requires report permission');
echo "$checks total checks passed in isolated database.\n";
if(in_array('--preview',$argv,true)){
 @mkdir('/private/tmp/planning-governance-preview',0777,true);
 file_put_contents('/private/tmp/planning-governance-preview/index.html',$strategyController->editor(requestFor($user),$revision->replicate())->render());
 file_put_contents('/private/tmp/planning-governance-preview/show.html',$controller->show(requestFor($user),$revision)->render());
}
