<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SiteLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'author_id',
        'log_date',
        'weather',
        'temperature',
        'manpower_count',
        'progress_percent',
        'summary',
        'issues',
        'safety_incident_occurred',
        'metrics',
    ];

    protected $casts = [
        'log_date' => 'date',
        'progress_percent' => 'float',
        'safety_incident_occurred' => 'boolean',
        'metrics' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'site_log_task')
            ->withPivot(['progress_percent', 'notes'])
            ->withTimestamps();
    }
}
