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
use App\Models\TaskStatusTransition;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class ProjectFlowDashboardService
{
    private ?DateTimeImmutable $lastTransitionAt = null;

    public function build(Project $project): ProjectDashboard
    {
        $project->loadMissing('tasks.assignee');

        $this->lastTransitionAt = null;

        $events = new DomainEvents();
        $board = new ProjectBoard(
            (string) $project->getKey(),
            $project->name ?? 'Unnamed project',
            $events
        );

        $insightTasks = [];
        $targetStatuses = [];

        /** @var Task $task */
        foreach ($project->tasks as $task) {
            [$insightTask, $targetStatus] = $this->makeInsightTask($task);
            $board->addTask($insightTask);
            $taskId = (int) $task->getKey();

            $insightTasks[$taskId] = $insightTask;
            $targetStatuses[$taskId] = $targetStatus;
        }

        $taskIds = array_keys($targetStatuses);
        $transitionsApplied = array_fill_keys($taskIds, false);

        if ($taskIds !== []) {
            $transitions = TaskStatusTransition::query()
                ->whereIn('task_id', $taskIds)
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->get();

            foreach ($transitions as $transition) {
                $taskId = (int) $transition->task_id;
                $status = $transition->to_status;

                if (! isset($targetStatuses[$taskId])) {
                    continue;
                }

                try {
                    WorkflowStatus::assertValid($status);
                } catch (InvalidArgumentException) {
                    continue;
                }

                $occurredAt = $transition->occurred_at
                    ? DateTimeImmutable::createFromInterface($transition->occurred_at)
                    : null;

                $board->moveTask((string) $taskId, $status, $occurredAt);
                $transitionsApplied[$taskId] = true;
                $this->registerTransitionTime($occurredAt);
            }
        }

        foreach ($targetStatuses as $taskId => $targetStatus) {
            if ($targetStatus === WorkflowStatus::BACKLOG) {
                continue;
            }

            $currentStatus = $insightTasks[$taskId]->status();
            $hasTransitions = $transitionsApplied[$taskId] ?? false;

            if (! $hasTransitions) {
                if ($currentStatus !== $targetStatus) {
                    $insightTasks[$taskId]->moveTo($targetStatus);
                }

                continue;
            }

            if ($currentStatus === $targetStatus) {
                continue;
            }

            $timestamp = new DateTimeImmutable('now');

            $board->moveTask((string) $taskId, $targetStatus, $timestamp);
            $this->registerTransitionTime($timestamp);
        }

        return new ProjectDashboard($board, $events);
    }

    public function snapshot(Project $project): array
    {
        $dashboard = $this->build($project);
        $generatedAt = new DateTimeImmutable('now');
        $projectUpdatedAt = $this->toImmutable($project->updated_at);

        return [
            'summary' => $dashboard->summary(),
            'focus' => $dashboard->focusSuggestion(),
            'meta' => [
                'generated_at' => $generatedAt->format(DateTimeInterface::ATOM),
                'project_updated_at' => $projectUpdatedAt?->format(DateTimeInterface::ATOM),
                'last_transition_at' => $this->lastTransitionAt?->format(DateTimeInterface::ATOM),
            ],
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

    private function registerTransitionTime(?DateTimeImmutable $occurredAt): void
    {
        if ($occurredAt === null) {
            return;
        }

        if ($this->lastTransitionAt === null || $occurredAt > $this->lastTransitionAt) {
            $this->lastTransitionAt = $occurredAt;
        }
    }
}
