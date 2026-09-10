<?php
namespace App\Models\Planning;
class Decision extends \Illuminate\Database\Eloquent\Model { 
protected $table="planning_decisions"; protected $guarded=[];
public function actor(){return $this->belongsTo(\App\Models\User::class,'actor_id');}
}
