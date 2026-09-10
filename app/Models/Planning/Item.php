<?php
namespace App\Models\Planning;
class Item extends \Illuminate\Database\Eloquent\Model { use \Illuminate\Database\Eloquent\Concerns\HasUuids;
protected $table="planning_items"; protected $guarded=[];
public function document(){return $this->belongsTo(Document::class);} public function owner(){return $this->belongsTo(\App\Models\User::class,'owner_id');}
}
