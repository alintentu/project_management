<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

use DateTimeImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Reactive dashboard surface combining flow metrics and actionable nudges.
 */
final class ProjectDashboard
{
    private ProjectBoard $project;

    public function __construct(ProjectBoard $project, DomainEvents $events)
    {
        $this->project = $project;

        $events->listen(TaskStatusChanged::class, function (TaskStatusChanged $event): void {
            $this->logStatusChange($event);
        });
    }

    public function focusSuggestion(): ?string
    {
        $reviewing = array_filter(
            $this->project->tasks(),
            static fn (InsightTask $task) => $task->status() === WorkflowStatus::IN_REVIEW
        );

        if ($reviewing !== []) {
            return sprintf('Finalize review for %s tasks before starting new work.', count($reviewing));
        }

        $wipBreaches = $this->project->wipLimitBreaches(5);

        if ($wipBreaches !== []) {
            return sprintf('WIP limit breached; finish tasks %s next.', implode(', ', $wipBreaches));
        }

        return 'Board flow looks good. Consider replenishing the backlog.';
    }

    public function summary(): array
    {
        $tasks = $this->project->tasks();
        $statusBuckets = array_fill_keys(WorkflowStatus::all(), 0);

        foreach ($tasks as $task) {
            $statusBuckets[$task->status()]++;
        }

        return [
            'total' => count($tasks),
            'status' => $statusBuckets,
            'velocity' => $this->project->velocityTrend(),
            'review_ratio' => $this->project->reviewRatio(),
            'transitions' => $this->project->transitions(),
            'cycle_time' => $this->project->cycleTimeMetrics(),
            'aging_wip' => $this->project->agingWorkInProgress(),
        ];
    }

    private function logStatusChange(TaskStatusChanged $event): void
    {
        Log::info('project_flow.task_status_changed', [
            'occurred_at' => $event->occurredAt()->format(DateTimeImmutable::ATOM),
            'task_id' => $event->taskId(),
            'previous_status' => $event->previous(),
            'next_status' => $event->next(),
        ]);
    }
}
