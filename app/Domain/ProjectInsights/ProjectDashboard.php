<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

use DateTimeImmutable;

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
        ];
    }

    private function logStatusChange(TaskStatusChanged $event): void
    {
        printf(
            "[%s] Task %s moved from %s to %s\n",
            $event->occurredAt()->format(DateTimeImmutable::ATOM),
            $event->taskId(),
            strtoupper($event->previous()),
            strtoupper($event->next())
        );
    }
}
