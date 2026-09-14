<?php

namespace App\Models\Planning;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasUuids;

    protected $table = 'planning_items';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
