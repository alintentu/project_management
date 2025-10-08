<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectFlowSnapshot;
use DateTimeImmutable;
use DateTimeInterface;

final class ProjectFlowSnapshotService
{
    public function __construct(
        private readonly ProjectFlowDashboardService $dashboardService
    ) {
    }

    public function capture(Project $project, ?DateTimeInterface $capturedAt = null): ProjectFlowSnapshot
    {
        $timestamp = $capturedAt instanceof DateTimeImmutable
            ? $capturedAt
            : DateTimeImmutable::createFromInterface($capturedAt ?? new DateTimeImmutable('now'));

        $payload = $this->dashboardService->snapshot($project);

        return ProjectFlowSnapshot::updateOrCreate(
            [
                'project_id' => $project->getKey(),
                'captured_at' => $timestamp,
            ],
            [
                'summary' => $payload['summary'],
                'alerts' => $payload['alerts'],
                'focus' => $payload['focus'],
                'meta' => $payload['meta'],
            ]
        );
    }
}
