<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResourceUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'code',
        'resource_type',
        'capacity',
        'cost_rate',
        'cost_rate_unit',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class)
            ->withPivot(['allocation_percent', 'planned_hours', 'actual_hours', 'notes'])
            ->withTimestamps();
    }
}
