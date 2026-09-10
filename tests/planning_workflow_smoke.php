<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Schema,Auth,Gate,Artisan};
use App\Models\{User,Role,Department,ApprovalChain,FiscalYear,Setting};
use App\Models\Planning\{Document,Task,Item};
use App\Services\Planning\{Access,Workflow};
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');Artisan::call('migrate',['--force'=>true]);
$role=Role::create(['name'=>'super_admin','display_name'=>'Super Admin']);
$dept=Department::create(['name'=>'Test Department','code'=>'TEST','is_active'=>true]);
$user=User::create(['name'=>'Planning Test','email'=>'planning-test@example.invalid','password'=>'unused','role_id'=>$role->id,'department_id'=>$dept->id,'is_active'=>true]);Auth::setUser($user);
$year=FiscalYear::create(['name'=>'2026/2027','is_legacy'=>false]);Setting::set('active_fiscal_year_id',$year->id,'integer');
$chain=ApprovalChain::create(['name'=>'Planning test','is_active'=>true]);$chain->syncApprovers([$user->id]);
$checks=0;function check($ok,$message){global $checks;if(!$ok)throw new RuntimeException($message);$checks++;echo "PASS $message\n";}
$plan=Document::create(['type'=>'plan','title'=>'Plan','department_id'=>$dept->id,'created_by'=>$user->id,'approval_chain_id'=>$chain->id]);
$item=$plan->items()->create(['title'=>'Training','requires_budget'=>true,'support_department_id'=>$dept->id]);
$workflow=new Workflow;$workflow->action($plan,$user,'submit',1);$plan->refresh();check($plan->status==='pending_approval','submit snapshots approval chain');$workflow->action($plan,$user,'accept',2);$plan->refresh();check($plan->status==='approved'&&Task::count()===1,'approval publishes one task');check(DB::table('planning_support_requests')->count()===1,'approval publishes support request');
try{$workflow->action($plan,$user,'accept',2);throw new RuntimeException('duplicate accepted');}catch(Illuminate\Validation\ValidationException $e){check(Task::count()===1,'stale approval cannot duplicate task');}
$budget=Document::create(['type'=>'budget','title'=>'Budget','department_id'=>$dept->id,'created_by'=>$user->id,'approval_chain_id'=>$chain->id,'parent_id'=>$plan->id,'fiscal_year_id'=>$year->id]);$budget->items()->create(['title'=>'Training allocation','source_id'=>$item->id,'amount'=>'15000.00']);$workflow->action($budget,$user,'submit',1);$workflow->action($budget,$user,'accept',2);check(App\Models\DepartmentBudget::count()===1,'approved allocation published');
$another=$budget->replicate();$another->status='draft';$another->version=1;$another->step_index=0;$another->save();$another->items()->create(['title'=>'Duplicate training','source_id'=>$item->id,'amount'=>'15000.00']);$workflow->action($another,$user,'submit',1);try{$workflow->action($another,$user,'accept',2);throw new RuntimeException('duplicate funding');}catch(Illuminate\Validation\ValidationException $e){check(App\Models\DepartmentBudget::count()===1,'duplicate funding blocked transactionally');}
$private=Task::create(['title'=>'Private','created_by'=>'another-user','assignee_id'=>'another-user','is_private'=>true]);check(!Access::tasks(Task::query(),$user)->whereKey($private->id)->exists(),'private task hidden even from another super admin');
view()->share(['errors'=>new Illuminate\Support\ViewErrorBag(),'workingFiscalYear'=>$year,'availableFiscalYears'=>collect([$year]),'fiscalYearWritable'=>true,'activeFiscalYearId'=>$year->id]);
$controller=app(App\Http\Controllers\Planning\PlanningController::class);
foreach(['strategy','goals','plan','budget'] as $type){$r=Illuminate\Http\Request::create('/planning','GET',['type'=>$type]);$r->setUserResolver(fn()=>$user);$app->instance('request',$r);check(strlen($controller->index($r)->render())>1000,"$type list renders");check(strlen($controller->create($r)->render())>1000,"$type editor renders");}
$r=Illuminate\Http\Request::create('/planning');$r->setUserResolver(fn()=>$user);$app->instance('request',$r);check(strlen($controller->show($r,$plan)->render())>1000,'detail renders');check(strlen($controller->setup($r)->render())>1000,'setup renders');check(strlen($controller->support($r)->render())>1000,'support renders');
foreach(['board','backlog','calendar'] as $mode){$r=Illuminate\Http\Request::create('/planning/tasks','GET',['mode'=>$mode]);$r->setUserResolver(fn()=>$user);$app->instance('request',$r);check(strlen(app(App\Http\Controllers\Planning\TaskController::class)->index($r)->render())>1000,"$mode renders");}
echo "$checks checks passed in isolated in-memory database.\n";
