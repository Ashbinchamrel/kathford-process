<?php

namespace App\Models\Planning;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasUuids;

    protected $table = 'planning_periods';

    protected $guarded = [];
}
