<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\ProjectInsights\DomainEvents;
use App\Domain\ProjectInsights\InsightTask;
use App\Domain\ProjectInsights\ProjectBoard;
use App\Domain\ProjectInsights\ProjectDashboard;
use App\Domain\ProjectInsights\WorkflowStatus;
use App\Enums\TaskStatus as TaskStatusEnum;
use App\Models\Project;
use App\Models\Task;
use DateTimeImmutable;
use DateTimeInterface;

final class ProjectFlowDashboardService
{
    public function build(Project $project): ProjectDashboard
    {
        $project->loadMissing('tasks.assignee');

        $events = new DomainEvents();
        $board = new ProjectBoard(
            (string) $project->getKey(),
            $project->name ?? 'Unnamed project',
            $events
        );

        /** @var Task $task */
        foreach ($project->tasks as $task) {
            [$insightTask, $targetStatus] = $this->makeInsightTask($task);
            $board->addTask($insightTask);

            if ($targetStatus !== WorkflowStatus::BACKLOG) {
                $board->moveTask($insightTask->id(), $targetStatus);
            }
        }

        return new ProjectDashboard($board, $events);
    }

    public function snapshot(Project $project): array
    {
        $dashboard = $this->build($project);

        return [
            'summary' => $dashboard->summary(),
            'focus' => $dashboard->focusSuggestion(),
        ];
    }

    /**
     * @return array{0: InsightTask, 1: string}
     */
    private function makeInsightTask(Task $task): array
    {
        $statusEnum = $task->status instanceof TaskStatusEnum
            ? $task->status
            : TaskStatusEnum::BACKLOG;

        $targetStatus = WorkflowStatus::fromEnum($statusEnum);

        $insightTask = new InsightTask(
            (string) $task->getKey(),
            $task->title ?? 'Untitled task',
            WorkflowStatus::BACKLOG,
            $this->toImmutable($task->created_at) ?? new DateTimeImmutable('now'),
            $this->toImmutable($task->due_date),
            $this->toImmutable($task->actual_end_date)
        );

        if ($task->assigned_to_id) {
            $insightTask->assign((string) $task->assigned_to_id);
        }

        if ($task->assignee) {
            $insightTask->assign((string) $task->assignee->getKey());
        }

        $metadata = is_array($task->metadata) ? $task->metadata : [];

        if (isset($metadata['tags']) && is_array($metadata['tags'])) {
            foreach ($metadata['tags'] as $tag) {
                if (is_string($tag)) {
                    $insightTask->tag($tag);
                }
            }
        }

        if (isset($metadata['review_notes']) && is_string($metadata['review_notes'])) {
            $insightTask->setReviewNotes($metadata['review_notes']);
        }

        return [$insightTask, $targetStatus];
    }

    private function toImmutable(DateTimeInterface|string|null $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable($value);
    }
}
