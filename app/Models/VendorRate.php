<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class VendorRate extends Model {
    use SoftDeletes;
    protected $guarded = ['id'];
    protected function casts(): array { return ['valid_from'=>'date','valid_until'=>'date','review_on'=>'date','is_active'=>'boolean']; }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function scopeAvailable($q) { return $q->where('is_active',true)->whereDate('valid_from','<=',today())->whereDate('valid_until','>=',today())->whereHas('vendor',fn($v)=>$v->where('is_active',true)); }
}
