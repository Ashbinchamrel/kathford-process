<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ChecklistQuestion extends Model {
    use SoftDeletes;
    protected $guarded=['id'];
    public static function snapshot(string $type): array {
        return static::where('is_active',true)->where('fulfillment_type',$type)->orderBy('sort_order')->orderBy('id')->get()->mapWithKeys(fn($q)=>['q_'.$q->id=>['label'=>$q->label,'required'=>(bool)$q->is_required]])->all();
    }
}
