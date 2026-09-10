<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RfqItem extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['quotation_not_required'=>'boolean']; }
    public function rfq() { return $this->belongsTo(Rfq::class); }
    public function vendorRate() { return $this->belongsTo(VendorRate::class); }
}
