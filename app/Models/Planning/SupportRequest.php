<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportRequest extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'planning_support_requests';

    protected $guarded = [];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
