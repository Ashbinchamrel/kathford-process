<?php

namespace App\Models\Planning;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasUuids;

    protected $table = 'planning_tasks';

    protected $guarded = [];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
