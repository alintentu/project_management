<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'status',
        'metadata',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'metadata' => 'array',
    ];

    public function wbsItems(): HasMany
    {
        return $this->hasMany(WorkBreakdownStructure::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(ResourceUnit::class);
    }

    public function siteLogs(): HasMany
    {
        return $this->hasMany(SiteLog::class);
    }
}
