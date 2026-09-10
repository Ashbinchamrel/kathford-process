<?php
namespace App\Services\Planning;
use App\Models\Planning\{Document,Task,SupportRequest};
use App\Models\{ApprovalChain,AuditLog,DepartmentBudget,FiscalYear,User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class Workflow {
 public function action(Document $document, User $actor, string $action, int $version, ?string $note=null): Document {
  return DB::transaction(function()use($document,$actor,$action,$version,$note){
   $d=Document::lockForUpdate()->findOrFail($document->id);Access::document($d,$actor);
   if((int)$d->version!==$version) $this->fail('This record changed. Reload it before continuing.');
   if($action==='submit'){
    abort_unless($actor->can('planning.'.$d->type.'_submit'),403);
    if(!in_array($d->status,['draft','returned']))$this->fail('Only a draft or returned document can be submitted.');
    if(!$d->items()->exists())$this->fail('Add at least one item before submitting.');
    if($d->parent_id && $d->parent?->status!=='approved')$this->fail('The linked parent must be approved first.');
    $chain=ApprovalChain::active()->with(['verifiers','approvers'])->find($d->approval_chain_id);
    if(!$chain||$chain->approvers->isEmpty())$this->fail('Choose an approval chain with at least one approver.');
    $steps=[];foreach(['verifiers'=>'verify','approvers'=>'approve'] as $relation=>$role) foreach($chain->$relation as $member){
     if(!$member->is_active||!$member->can('planning.'.$d->type.'_'.$role)||!$member->can('planning.'.$d->type.'_view')||!$member->can('planning.view'))$this->fail('Every chain member needs active Planning access and the matching view/decision permission.');
     $steps[]=['user_id'=>$member->id,'name'=>$member->name,'action'=>$role];
    }
    $d->steps=$steps;$d->step_index=0;$d->status=$steps[0]['action']==='verify'?'pending_verification':'pending_approval';
   }else{
    $step=$d->step();if(!in_array($d->status,['pending_verification','pending_approval'])||!$step)$this->fail('This document is not awaiting a decision.');
    abort_unless($step['user_id']===$actor->id && $actor->can('planning.'.$d->type.'_'.$step['action']),403);
    if(in_array($action,['return','reject'])){if(blank($note))$this->fail('Enter a reason.');$d->status=$action==='return'?'returned':'rejected';}
    elseif($action==='accept'){
     $d->step_index++;$next=$d->step();$d->status=$next?($next['action']==='verify'?'pending_verification':'pending_approval'):'approved';
     if(!$next){$d->approved_at=now();$this->publish($d,$actor);}
    }else $this->fail('Invalid decision.');
   }
   $d->version++;$d->save();$d->decisions()->create(['actor_id'=>$actor->id,'decision'=>$action,'note'=>$note]);
   AuditLog::record($actor,'planning.'.$action,$d,$d->title);return $d;
  });
 }
 private function publish(Document $d,User $actor): void {
  if($d->type==='plan')foreach($d->items as $item){
   Task::firstOrCreate(['item_id'=>$item->id],['department_id'=>$d->department_id,'created_by'=>$d->created_by,'assignee_id'=>$item->owner_id ?: $d->created_by,'title'=>$item->title,'description'=>$item->description,'definition_of_done'=>$item->definition_of_done,'due_on'=>$item->due_on]);
   if($item->support_department_id)SupportRequest::firstOrCreate(['item_id'=>$item->id],['department_id'=>$item->support_department_id]);
  }
  if($d->type==='budget'){
   $context=app(\App\Support\FiscalYearContext::class);$context->assertWritable();
   $year=FiscalYear::findOrFail($d->fiscal_year_id);
   if((int)\App\Models\Setting::get('active_fiscal_year_id')!==$year->id)$this->fail('Only the active fiscal year can receive allocations.');
   foreach($d->items as $item){
    if(DB::table('planning_budget_publications')->where('item_id',$item->id)->exists())continue;
    $source=\App\Models\Planning\Item::with('document')->find($item->source_id);
    if(!$source || $source->document->type!=='plan'||$source->document->status!=='approved'||$source->document->department_id!==$d->department_id||!$source->requires_budget)$this->fail('Every budget line must link to an approved funded activity in this department.');
    // Serialize all funding publications for a source activity across separate proposals.
    DB::table('planning_items')->where('id',$source->id)->lockForUpdate()->first();
    $existing=DB::table('planning_budget_publications as p')->join('planning_items as i','i.id','=','p.item_id')->join('planning_documents as d','d.id','=','i.document_id')->where('i.source_id',$source->id)->where('d.fiscal_year_id',$year->id)->exists();
    if($existing)$this->fail('This activity already has an allocation for this fiscal year.');
    if((float)$item->amount<=0)$this->fail('Budget allocations must be greater than zero.');
    $budget=new DepartmentBudget(['department_id'=>$d->department_id,'fiscal_year'=>$year->name,'activity_title'=>mb_substr($source->title,0,160).' · '.substr($source->id,-8),'allocated_amount'=>$item->amount,'notes'=>'Approved through Planning: '.$d->title,'is_active'=>true,'created_by'=>$actor->id,'fiscal_year_id'=>$year->id]);$budget->fiscal_year_id=$year->id;$budget->save();
    DB::table('planning_budget_publications')->insert(['item_id'=>$item->id,'budget_id'=>$budget->id,'created_at'=>now(),'updated_at'=>now()]);
   }
  }
 }
 private function fail(string $message): never {throw ValidationException::withMessages(['planning'=>$message]);}
}
