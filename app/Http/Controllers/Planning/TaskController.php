<?php
namespace App\Http\Controllers\Planning;
use App\Http\Controllers\Controller;
use App\Models\Planning\{Task,Sprint};
use App\Models\{User,Department,AuditLog};
use App\Services\Planning\Access;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class TaskController extends Controller {
 public function index(Request $r){
  $q=Access::tasks(Task::with('assignee','sprint','item.document'),$r->user());
  $mode=$r->input('mode','board');abort_unless(in_array($mode,['board','backlog','calendar']),404);
  if($r->filled('search'))$q->where('title','like','%'.$r->string('search')->trim().'%');
  if($r->filled('sprint'))$q->where('sprint_id',$r->sprint);
  if($mode==='calendar'){$month=$r->validate(['month'=>'nullable|date_format:Y-m'])['month']??now()->format('Y-m');$start=\Carbon\Carbon::createFromFormat('Y-m-d',$month.'-01')->startOfDay();$q->where('assignee_id',$r->user()->id)->whereBetween('due_on',[$start->format('Y-m-d'),$start->copy()->endOfMonth()->format('Y-m-d')]);}
  $sprints=Sprint::query();if(!$r->user()->isSuperAdmin()&&!$r->user()->can('planning.all_departments'))$sprints->where('department_id',$r->user()->department_id??'none');
  return view('planning.tasks',['tasks'=>$q->orderBy('due_on')->paginate(60)->withQueryString(),'mode'=>$mode,'month'=>$month??now()->format('Y-m'),'sprints'=>$sprints->latest()->get(),'departments'=>Department::active()->get()->filter(fn($d)=>Access::department($r->user(),$d->id)),'users'=>User::active()->get()->filter(fn($u)=>$u->id===$r->user()->id||Access::department($r->user(),$u->department_id))]);
 }
 public function store(Request $r){
  $data=$r->validate(['title'=>'required|string|max:200','description'=>'nullable|string|max:5000','department_id'=>'nullable|exists:departments,id','assignee_id'=>'required|exists:users,id','reviewer_id'=>'nullable|exists:users,id','due_on'=>'nullable|date_format:Y-m-d','definition_of_done'=>'nullable|string|max:5000','is_private'=>'nullable|boolean','priority'=>'required|in:low,normal,high']);
  $private=$r->boolean('is_private');if($private){$data['department_id']=null;$data['assignee_id']=$r->user()->id;$data['reviewer_id']=null;}else{abort_unless($r->user()->can('planning.tasks')&&Access::department($r->user(),$data['department_id']??null),403);foreach(['assignee_id','reviewer_id'] as $field)if(!empty($data[$field]))abort_unless(User::active()->whereKey($data[$field])->where('department_id',$data['department_id'])->exists(),422,'Choose team members from the task department.');}
  $task=Task::create($data+['created_by'=>$r->user()->id]);AuditLog::record($r->user(),'planning.task.created',$task,$task->title);return back()->with('success','Task created.');
 }
 public function update(Request $r,Task $task){
  abort_unless(Access::tasks(Task::query(),$r->user())->whereKey($task->id)->exists(),403);
  $data=$r->validate(['version'=>'required|integer','status'=>'required|in:todo,in_progress,review,done','sprint_id'=>'nullable|exists:planning_sprints,id','evidence'=>'nullable|string|max:5000','blocked_reason'=>'nullable|string|max:1000','note'=>'nullable|string|max:5000']);
  DB::transaction(function()use($r,$task,$data){$t=Task::lockForUpdate()->findOrFail($task->id);abort_unless((int)$t->version===(int)$data['version'],409,'Task changed; reload before saving.');
   abort_unless($t->is_private ? $t->created_by===$r->user()->id : $r->user()->can('planning.tasks')&&($t->assignee_id===$r->user()->id||$t->reviewer_id===$r->user()->id||Access::department($r->user(),$t->department_id)),403);
   if(!empty($data['sprint_id'])){$s=Sprint::findOrFail($data['sprint_id']);abort_unless(!$t->is_private&&$s->department_id===$t->department_id&&$s->status!=='completed',422,'Choose an open sprint for this department.');}
   if($data['status']==='done' && $t->status!=='done' && !$t->is_private){abort_unless($t->status==='review' && ($t->reviewer_id ? $t->reviewer_id===$r->user()->id : $t->created_by===$r->user()->id),422,'The designated reviewer (or task creator) must accept the task from Review.');if(blank($data['evidence']??null))throw \Illuminate\Validation\ValidationException::withMessages(['evidence'=>'Record evidence that the Definition of Done has been met.']);}
   if($t->status==='done'&&$data['status']!=='done'&&blank($data['note']??null))throw \Illuminate\Validation\ValidationException::withMessages(['note'=>'Give a reason for reopening.']);
   DB::table('planning_task_events')->insert(['task_id'=>$t->id,'actor_id'=>$r->user()->id,'action'=>$t->status.' → '.$data['status'],'note'=>$data['note']??null,'created_at'=>now(),'updated_at'=>now()]);unset($data['note'],$data['version']);$t->fill($data);$t->version++;$t->save();
  });return back()->with('success','Task updated.');
 }
 public function sprint(Request $r){abort_unless($r->user()->can('planning.tasks'),403);$data=$r->validate(['name'=>'required|string|max:150','goal'=>'required|string|max:2000','department_id'=>'required|exists:departments,id','starts_on'=>'required|date_format:Y-m-d','ends_on'=>'required|date_format:Y-m-d|after_or_equal:starts_on']);abort_unless(Access::department($r->user(),$data['department_id']),403);$s=Sprint::create($data);AuditLog::record($r->user(),'planning.sprint.created',$s,$s->name);return back()->with('success','Sprint created.');}
 public function sprintAction(Request $r,Sprint $sprint){abort_unless($r->user()->can('planning.tasks')&&Access::department($r->user(),$sprint->department_id),403);$v=$r->validate(['action'=>'required|in:start,complete']);DB::transaction(function()use($sprint,$v,$r){$s=Sprint::lockForUpdate()->findOrFail($sprint->id);abort_unless(($v['action']==='start'&&$s->status==='planned')||($v['action']==='complete'&&$s->status==='active'),422);if($v['action']==='complete')Task::where('sprint_id',$s->id)->where('status','!=','done')->update(['sprint_id'=>null,'version'=>DB::raw('version + 1')]);$s->update(['status'=>$v['action']==='start'?'active':'completed']);AuditLog::record($r->user(),'planning.sprint.'.$v['action'],$s,$s->name);});return back()->with('success','Sprint updated. Unfinished work remains in the backlog.');}
}
