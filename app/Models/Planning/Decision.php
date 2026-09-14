<?php

namespace App\Models\Planning;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    protected $table = 'planning_decisions';

    protected $guarded = [];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
