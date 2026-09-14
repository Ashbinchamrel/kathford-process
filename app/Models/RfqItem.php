<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RfqItem extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['quotation_not_required'=>'boolean', 'available_in_store'=>'boolean', 'store_issued_at'=>'datetime']; }
    public function rfq() { return $this->belongsTo(Rfq::class); }
    public function vendorRate() { return $this->belongsTo(VendorRate::class); }
    public function storeIssuedBy(): BelongsTo { return $this->belongsTo(User::class, 'store_issued_by'); }
    public function isStoreIssued(): bool { return $this->available_in_store && $this->store_issued_at !== null; }
}
