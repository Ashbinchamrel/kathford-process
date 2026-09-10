<?php
namespace App\Models\Planning;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Document extends Model {
 use HasUuids;
 protected $table='planning_documents'; protected $guarded=[];
 protected function casts(): array {return ['steps'=>'array','approved_at'=>'datetime'];}
 public function items(){return $this->hasMany(Item::class,'document_id');}
 public function department(){return $this->belongsTo(\App\Models\Department::class);}
 public function period(){return $this->belongsTo(Period::class);}
 public function parent(){return $this->belongsTo(self::class,'parent_id');}
 public function decisions(){return $this->hasMany(Decision::class,'document_id');}
 public function creator(){return $this->belongsTo(\App\Models\User::class,'created_by');}
 public function step(): ?array {return ($this->steps ?? [])[$this->step_index] ?? null;}
}
