<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFlowSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'captured_at',
        'summary',
        'alerts',
        'focus',
        'meta',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'summary' => 'array',
        'alerts' => 'array',
        'meta' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function briefFocus(): Attribute
    {
        return Attribute::get(fn ($value, array $attributes) => $attributes['focus'] ?? null);
    }
}
