<?php
namespace App\Services\Planning;
use App\Models\Planning\Document;
use App\Models\User;
class Access {
 const TYPES=['strategy'=>'Strategic Plans','goals'=>'Semester Goals','plan'=>'Semester Plans','budget'=>'Budget Proposals'];
 public static function permissions(): array {
  $p=['planning.view'=>'Open Planning','planning.all_departments'=>'Access all departments','planning.setup'=>'Manage periods and Planning setup','planning.tasks'=>'Manage team tasks and sprints','planning.support'=>'Respond to support requests'];
  foreach(self::TYPES as $type=>$label) foreach(['view','create','submit','verify','approve'] as $action) $p["planning.{$type}_{$action}"]=ucfirst($action).' '.$label;
  return $p;
 }
 public static function department(User $u, ?string $id): bool {return $u->isSuperAdmin()||$u->can('planning.all_departments')||($id && $u->department_id===$id);}
 public static function documents($q, User $u) {
  if($u->isSuperAdmin()||$u->can('planning.all_departments')) return $q;
  return $q->where(function($q)use($u){$q->where('created_by',$u->id)->orWhere(function($q)use($u){$q->whereNotNull('department_id')->where('department_id',$u->department_id ?? 'none');})->orWhereJsonContains('steps',['user_id'=>$u->id])->orWhere(fn($q)=>$q->where('type','strategy')->where('status','approved'));});
 }
 public static function document(Document $d, User $u): void {abort_unless($u->can('planning.'.$d->type.'_view') && self::documents(Document::query(),$u)->whereKey($d->id)->exists(),403);}
 public static function tasks($q,User $u){return $q->where(function($q)use($u){$q->where(fn($q)=>$q->where('is_private',true)->where('created_by',$u->id))->orWhere(function($q)use($u){$q->where('is_private',false);if(!$u->isSuperAdmin()&&!$u->can('planning.all_departments'))$q->where(fn($q)=>$q->where('assignee_id',$u->id)->orWhere('created_by',$u->id)->orWhere('reviewer_id',$u->id)->orWhere('department_id',$u->department_id ?? 'none'));});});}
}
