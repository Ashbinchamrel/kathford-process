<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    use HasUuids;

    protected $table = 'planning_sprints';

    protected $guarded = [];
}
