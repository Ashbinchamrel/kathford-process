<?php
namespace App\Http\Controllers\Planning;
use App\Http\Controllers\Controller;
use App\Models\Planning\{Document,Item,Period,Task,Sprint,SupportRequest};
use App\Models\{Department,User,ApprovalChain,AuditLog};
use App\Services\Planning\{Access,Workflow};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class PlanningController extends Controller {
 public function index(Request $r){
  $type=$r->input('type');if(!$type){foreach(array_keys(Access::TYPES) as $candidate)if($r->user()->can('planning.'.$candidate.'_view')){$type=$candidate;break;}if(!$type)return redirect()->route('planning.tasks');}abort_unless(isset(Access::TYPES[$type])&&$r->user()->can('planning.'.$type.'_view'),403);
  $q=Access::documents(Document::with('department','period'),$r->user())->where('type',$type);
  if($r->filled('search'))$q->where('title','like','%'.$r->string('search')->trim().'%');
  if($r->filled('status'))$q->where('status',$r->status);
  return view('planning.index',['documents'=>$q->latest()->paginate(20)->withQueryString(),'type'=>$type]);
 }
 private function lookups(Request $r):array {
  $departments=Department::active()->get()->filter(fn($d)=>Access::department($r->user(),$d->id));
  return ['departments'=>$departments,'supportDepartments'=>Department::active()->get(),'users'=>User::active()->orderBy('name')->get(['id','name','department_id']),'periods'=>Period::orderByDesc('starts_on')->get(),'chains'=>ApprovalChain::active()->get(),'parents'=>Access::documents(Document::query(),$r->user())->where('status','approved')->get()];
 }
 public function create(Request $r){$type=$r->input('type','strategy');abort_unless(isset(Access::TYPES[$type])&&$r->user()->can('planning.'.$type.'_create'),403);return view('planning.edit',$this->lookups($r)+['document'=>new Document(['type'=>$type]),'items'=>collect()]);}
 public function store(Request $r){$d=new Document(['created_by'=>$r->user()->id]);return $this->save($r,$d);}
 public function edit(Request $r,Document $document){Access::document($document,$r->user());abort_unless(in_array($document->status,['draft','returned'])&&$r->user()->can('planning.'.$document->type.'_create'),403);return view('planning.edit',$this->lookups($r)+['document'=>$document,'items'=>$document->items]);}
 public function update(Request $r,Document $document){Access::document($document,$r->user());return $this->save($r,$document);}
 private function save(Request $r,Document $document){
  $data=$r->validate(['type'=>['required',Rule::in(array_keys(Access::TYPES))],'title'=>'required|string|max:200','department_id'=>'nullable|exists:departments,id','period_id'=>'nullable|exists:planning_periods,id','parent_id'=>'nullable|exists:planning_documents,id','approval_chain_id'=>'required|exists:approval_chains,id','description'=>'nullable|string|max:20000','version'=>'nullable|integer','items'=>'required|array|min:1|max:200','items.*.title'=>'required|string|max:200','items.*.description'=>'nullable|string|max:5000','items.*.owner_id'=>'nullable|exists:users,id','items.*.due_on'=>'nullable|date_format:Y-m-d','items.*.definition_of_done'=>'nullable|string|max:5000','items.*.metric'=>'nullable|string|max:100','items.*.baseline'=>'nullable|numeric|min:0|max:999999999999','items.*.target'=>'nullable|numeric|min:0|max:999999999999','items.*.amount'=>'nullable|numeric|min:0|max:999999999999','items.*.requires_budget'=>'nullable|boolean','items.*.support_department_id'=>'nullable|exists:departments,id','items.*.source_id'=>'nullable|exists:planning_items,id','items.*.programme'=>'nullable|string|max:100','items.*.batch'=>'nullable|string|max:100']);
  abort_unless($r->user()->can('planning.'.$data['type'].'_create'),403);
  if($data['type']!=='strategy')abort_unless(Access::department($r->user(),$data['department_id']??null),403);
  if(!empty($data['parent_id'])){$parent=Document::findOrFail($data['parent_id']);Access::document($parent,$r->user());$expected=['goals'=>'strategy','plan'=>'goals','budget'=>'plan'][$data['type']]??null;abort_unless($expected===$parent->type && $parent->status==='approved' && ($parent->type==='strategy'||$parent->department_id===$data['department_id']),422,'Choose an approved parent of the matching type and department.');}
  if($data['type']==='budget')app(\App\Support\FiscalYearContext::class)->assertWritable();
  foreach($data['items'] as $item){if(!empty($item['owner_id'])){ $owner=User::active()->findOrFail($item['owner_id']);abort_unless($data['type']==='strategy'||$owner->department_id===($data['department_id']??null),422,'Choose an activity owner in the plan department.');}}
  DB::transaction(function()use($data,$document,$r){
   $d=$document->exists?Document::lockForUpdate()->findOrFail($document->id):$document;
   if($d->exists)abort_unless(in_array($d->status,['draft','returned']) && $d->type===$data['type'] && (int)$d->version===(int)($data['version']??0),409,'This document changed. Reload before saving.');
   $items=$data['items'];unset($data['items'],$data['version']);$d->fill($data);if($d->type==='budget')$d->fiscal_year_id=app(\App\Support\FiscalYearContext::class)->year?->id;$d->version++;$d->save();$d->items()->delete();
   foreach($items as $item){$item['requires_budget']=!empty($item['requires_budget']);$item['amount']=$item['amount']??0;$d->items()->create($item);}
   $document->id=$d->id;AuditLog::record($r->user(),'planning.saved',$d,$d->title);
  });return redirect()->route('planning.show',$document)->with('success','Planning draft saved.');
 }
 public function show(Request $r,Document $document){Access::document($document,$r->user());return view('planning.show',['document'=>$document->load('items.owner','decisions.actor','department','parent','period'),'publications'=>DB::table('planning_budget_publications')->whereIn('item_id',$document->items->pluck('id'))->pluck('budget_id','item_id')]);}
 public function action(Request $r,Document $document,Workflow $workflow){$v=$r->validate(['action'=>'required|in:submit,accept,return,reject','version'=>'required|integer','note'=>'nullable|string|max:5000']);$workflow->action($document,$r->user(),$v['action'],$v['version'],$v['note']??null);return back()->with('success','Workflow updated.');}
 public function setup(Request $r){abort_unless($r->user()->can('planning.setup'),403);return view('planning.setup',['periods'=>Period::orderByDesc('starts_on')->get()]);}
 public function period(Request $r){abort_unless($r->user()->can('planning.setup'),403);$data=$r->validate(['name'=>'required|string|max:150','academic_year'=>'required|string|max:30','programme'=>'nullable|string|max:100','batch'=>'nullable|string|max:100','starts_on'=>'required|date_format:Y-m-d','ends_on'=>'required|date_format:Y-m-d|after_or_equal:starts_on']);$p=Period::create($data);AuditLog::record($r->user(),'planning.period.created',$p,$p->name);return back()->with('success','Planning period added.');}
 public function support(Request $r){abort_unless($r->user()->can('planning.support'),403);$q=SupportRequest::with('item.document');if(!$r->user()->isSuperAdmin()&&!$r->user()->can('planning.all_departments'))$q->where('department_id',$r->user()->department_id??'none');return view('planning.support',['requests'=>$q->latest()->paginate(20)]);}
 public function respond(Request $r,SupportRequest $support){abort_unless($r->user()->can('planning.support')&&Access::department($r->user(),$support->department_id),403);$support->update($r->validate(['status'=>'required|in:requested,accepted,completed,declined','response'=>'required|string|max:5000']));AuditLog::record($r->user(),'planning.support.responded',$support);return back()->with('success','Support request updated.');}
}
