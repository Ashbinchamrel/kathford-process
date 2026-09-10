<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProcurementReturn extends Model {
    protected $guarded=['id'];
    public function checklist(){return $this->belongsTo(ProcurementChecklist::class);}
    public function vendor(){return $this->belongsTo(Vendor::class);}
}
