<?php
namespace App\Models\Planning;
class Task extends \Illuminate\Database\Eloquent\Model { use \Illuminate\Database\Eloquent\Concerns\HasUuids;
protected $table="planning_tasks"; protected $guarded=[];
public function assignee(){return $this->belongsTo(\App\Models\User::class,'assignee_id');} public function sprint(){return $this->belongsTo(Sprint::class);} public function item(){return $this->belongsTo(Item::class);}
}
