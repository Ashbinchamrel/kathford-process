<?php

namespace App\Models\Planning;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasUuids;

    protected $table = 'planning_documents';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['steps' => 'array', 'strategy_data' => 'array', 'approved_at' => 'datetime'];
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'document_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function period()
    {
        return $this->belongsTo(Period::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function decisions()
    {
        return $this->hasMany(Decision::class, 'document_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function step(): ?array
    {
        return ($this->steps ?? [])[$this->step_index] ?? null;
    }
}
