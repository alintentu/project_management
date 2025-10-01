<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ResourceUnit;
use App\Models\SiteLog;
use App\Models\User;
use App\Models\WorkBreakdownStructure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Task extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'description',
        'status',
        'assigned_to_id',
        'due_date',
        'position',
        'project_id',
        'wbs_id',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'planned_duration_days',
        'actual_duration_days',
        'progress_percent',
        'metadata',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'due_date' => 'date',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'progress_percent' => 'float',
        'metadata' => 'array',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function scopeStatus(Builder $query, TaskStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->useDisk('public');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function wbs(): BelongsTo
    {
        return $this->belongsTo(WorkBreakdownStructure::class, 'wbs_id');
    }

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(ResourceUnit::class)
            ->withPivot(['allocation_percent', 'planned_hours', 'actual_hours', 'notes'])
            ->withTimestamps();
    }

    public function siteLogs(): BelongsToMany
    {
        return $this->belongsToMany(SiteLog::class, 'site_log_task')
            ->withPivot(['progress_percent', 'notes'])
            ->withTimestamps();
    }
}
