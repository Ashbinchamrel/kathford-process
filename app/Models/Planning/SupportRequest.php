<?php
namespace App\Models\Planning;
class SupportRequest extends \Illuminate\Database\Eloquent\Model { use \Illuminate\Database\Eloquent\Concerns\HasUuids;
protected $table="planning_support_requests"; protected $guarded=[];
public function item(){return $this->belongsTo(Item::class);}
}
