<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\ProjectInsights\DomainEvents;
use App\Domain\ProjectInsights\FlowAlert;
use App\Domain\ProjectInsights\InsightTask;
use App\Domain\ProjectInsights\ProjectBoard;
use App\Domain\ProjectInsights\ProjectDashboard;
use App\Domain\ProjectInsights\WorkflowStatus;
use App\Enums\TaskStatus as TaskStatusEnum;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatusTransition;
use App\Models\ProjectFlowSnapshot;
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
        $board = $this->makeBoard($project, $events);

        [$insightTasks, $targetStatuses] = $this->hydrateBoard($board, $project->tasks);

        $transitionsApplied = $this->applyTransitions($board, $targetStatuses);

        $this->synchronizeTargetStatuses($board, $insightTasks, $targetStatuses, $transitionsApplied);

        return new ProjectDashboard($board, $events);
    }

    /**
     * @return array{
     *     summary: array<string, mixed>,
     *     focus: ?string,
     *     meta: array{
     *         generated_at: string,
     *         project_updated_at: ?string,
     *         last_transition_at: ?string
     *     }
     * }
     */
    public function snapshot(Project $project): array
    {
        $dashboard = $this->build($project);
        $generatedAt = new DateTimeImmutable('now');
        $projectUpdatedAt = $this->toImmutable($project->updated_at);

        $summary = $dashboard->summary();
        $focus = $dashboard->focusSuggestion();
        $alerts = $dashboard->alerts();

        $history = $this->trendPoints($project, $summary, $alerts, $focus, $generatedAt);

        return [
            'summary' => $summary,
            'focus' => $focus,
            'alerts' => array_map(
                static fn (FlowAlert $alert) => $alert->toArray(),
                $alerts
            ),
            'trend' => [
                'points' => $history,
            ],
            'meta' => [
                'generated_at' => $generatedAt->format(DateTimeInterface::ATOM),
                'project_updated_at' => $projectUpdatedAt?->format(DateTimeInterface::ATOM),
                'last_transition_at' => $this->lastTransitionAt?->format(DateTimeInterface::ATOM),
            ],
        ];
    }

    private function makeBoard(Project $project, DomainEvents $events): ProjectBoard
    {
        return new ProjectBoard(
            (string) $project->getKey(),
            $project->name ?? 'Unnamed project',
            $events
        );
    }

    /**
     * @param iterable<Task> $tasks
     * @return array{
     *     0: array<int, InsightTask>,
     *     1: array<int, string>
     * }
     */
    private function hydrateBoard(ProjectBoard $board, iterable $tasks): array
    {
        $insightTasks = [];
        $targetStatuses = [];

        /** @var Task $task */
        foreach ($tasks as $task) {
            [$insightTask, $targetStatus] = $this->makeInsightTask($task);
            $board->addTask($insightTask);

            $taskId = (int) $task->getKey();
            $insightTasks[$taskId] = $insightTask;
            $targetStatuses[$taskId] = $targetStatus;
        }

        return [$insightTasks, $targetStatuses];
    }

    /**
     * @param array<int, string> $targetStatuses
     * @return array<int, bool>
     */
    private function applyTransitions(ProjectBoard $board, array $targetStatuses): array
    {
        if ($targetStatuses === []) {
            return [];
        }

        $taskIds = array_keys($targetStatuses);
        $transitionsApplied = array_fill_keys($taskIds, false);

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

        return $transitionsApplied;
    }

    /**
     * @param array<int, InsightTask> $insightTasks
     * @param array<int, string> $targetStatuses
     * @param array<int, bool> $transitionsApplied
     */
    private function synchronizeTargetStatuses(
        ProjectBoard $board,
        array $insightTasks,
        array $targetStatuses,
        array $transitionsApplied
    ): void {
        foreach ($targetStatuses as $taskId => $targetStatus) {
            if ($targetStatus === WorkflowStatus::BACKLOG) {
                continue;
            }

            $task = $insightTasks[$taskId] ?? null;

            if ($task === null) {
                continue;
            }

            $currentStatus = $task->status();
            $hasTransitions = $transitionsApplied[$taskId] ?? false;

            if (! $hasTransitions) {
                if ($currentStatus !== $targetStatus) {
                    $task->moveTo($targetStatus);
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
    }

    /**
     * @param array<string, mixed> $summary
     * @param array<int, FlowAlert> $alerts
     * @return array<int, array<string, mixed>>
     */
    private function trendPoints(
        Project $project,
        array $summary,
        array $alerts,
        ?string $focus,
        DateTimeImmutable $generatedAt,
        int $limit = 50
    ): array {
        $history = ProjectFlowSnapshot::query()
            ->where('project_id', $project->getKey())
            ->orderByDesc('captured_at')
            ->limit($limit)
            ->get();

        $points = [];

        foreach ($history as $snapshot) {
            $points[] = $this->makeTrendPoint(
                $this->toImmutable($snapshot->captured_at) ?? new DateTimeImmutable('now'),
                is_array($snapshot->summary) ? $snapshot->summary : [],
                is_array($snapshot->alerts) ? $snapshot->alerts : [],
                $snapshot->focus
            );
        }

        $currentPoint = $this->makeTrendPoint($generatedAt, $summary, array_map(
            static fn (FlowAlert $alert) => $alert->toArray(),
            $alerts
        ), $focus);

        if ($points === [] || ($points[0]['captured_at'] ?? null) !== $currentPoint['captured_at']) {
            $points[] = $currentPoint;
        }

        usort($points, static fn (array $a, array $b) => strcmp($a['captured_at'], $b['captured_at']));

        return $points;
    }

    /**
     * @param array<string, mixed> $summary
     * @param array<int, mixed> $alerts
     * @return array<string, mixed>
     */
    private function makeTrendPoint(DateTimeInterface $capturedAt, array $summary, array $alerts, ?string $focus): array
    {
        if (! $capturedAt instanceof DateTimeImmutable) {
            $capturedAt = DateTimeImmutable::createFromInterface($capturedAt);
        }

        $status = $summary['status'] ?? [];
        $cycle = $summary['cycle_time'] ?? [];
        $aging = $summary['aging_wip'] ?? [];

        $maxAge = null;

        if (is_array($aging) && $aging !== []) {
            $maxAge = max(array_map(static fn ($item) => is_array($item) ? ($item['age_days'] ?? null) : null, $aging));
            $maxAge = is_numeric($maxAge) ? (float) $maxAge : null;
        }

        return [
            'captured_at' => $capturedAt->format(DateTimeInterface::ATOM),
            'total' => (int) ($summary['total'] ?? 0),
            'backlog' => (int) ($status[WorkflowStatus::BACKLOG] ?? 0),
            'in_progress' => (int) ($status[WorkflowStatus::IN_PROGRESS] ?? 0),
            'in_review' => (int) ($status[WorkflowStatus::IN_REVIEW] ?? 0),
            'done' => (int) ($status[WorkflowStatus::DONE] ?? 0),
            'review_ratio' => isset($summary['review_ratio']) ? (float) $summary['review_ratio'] : 0.0,
            'cycle_time_average_days' => isset($cycle['average_days']) ? (float) $cycle['average_days'] : null,
            'cycle_time_samples' => (int) ($cycle['samples'] ?? 0),
            'alerts_count' => is_countable($alerts) ? count($alerts) : 0,
            'max_aging_days' => $maxAge,
            'focus' => $focus,
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
