<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Aggregate tracking task flow, transitions, and derived metrics.
 */
final class ProjectBoard
{
    private string $id;
    private string $name;

    /** @var array<string, InsightTask> */
    private array $tasks = [];

    private DomainEvents $events;

    /** @var array<string, int> */
    private array $statusTransitions = [];

    public function __construct(string $id, string $name, ?DomainEvents $events = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->events = $events ?? new DomainEvents();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function addTask(InsightTask $task): void
    {
        $this->assertMissing($task->id());
        $this->tasks[$task->id()] = $task;
    }

    /**
     * @return array<int, InsightTask>
     */
    public function tasks(): array
    {
        return array_values($this->tasks);
    }

    public function moveTask(string $taskId, string $status, ?DateTimeImmutable $occurredAt = null): void
    {
        $task = $this->assertPresent($taskId);
        $previousStatus = $task->status();

        if ($previousStatus === $status) {
            return;
        }

        $task->moveTo($status, $occurredAt);

        $this->statusTransitions[$status] ??= 0;
        $this->statusTransitions[$status]++;

        $eventTimestamp = $occurredAt ?? new DateTimeImmutable('now');

        $this->events->dispatch(new TaskStatusChanged($taskId, $previousStatus, $status, $eventTimestamp));
    }

    /**
     * @return array<string, int>
     */
    public function transitions(): array
    {
        return $this->statusTransitions;
    }

    /**
     * @return array<string, int>
     */
    public function velocityTrend(int $weeks = 4): array
    {
        $now = new DateTimeImmutable('monday this week');
        $period = new DatePeriod($now->sub(new DateInterval("P{$weeks}W")), new DateInterval('P1W'), $weeks);
        $velocity = [];

        foreach ($period as $week) {
            $weekStart = $week;
            $weekEnd = $week->add(new DateInterval('P7D'));
            $velocity[$weekStart->format('Y-m-d')] = $this->doneBetween($weekStart, $weekEnd);
        }

        return $velocity;
    }

    public function reviewRatio(): float
    {
        $total = count($this->tasks);

        if ($total === 0) {
            return 0.0;
        }

        $inReview = count(array_filter(
            $this->tasks,
            static fn (InsightTask $task) => $task->status() === WorkflowStatus::IN_REVIEW
        ));

        return $inReview / $total;
    }

    /**
     * Returns identifiers of tasks that push the current WIP limit.
     *
     * @return array<int, string>
     */
    public function wipLimitBreaches(int $limit): array
    {
        $breaches = [];

        foreach ($this->tasks as $task) {
            if (in_array($task->status(), [WorkflowStatus::IN_PROGRESS, WorkflowStatus::IN_REVIEW], true)) {
                $breaches[] = $task->id();
            }

            if (count($breaches) >= $limit) {
                break;
            }
        }

        return $breaches;
    }

    /**
     * @return array{average_days: float, median_days: float, samples: int}
     */
    public function cycleTimeMetrics(): array
    {
        $durations = [];

        foreach ($this->tasks as $task) {
            $completedAt = $task->completedAt();

            if ($completedAt === null) {
                continue;
            }

            $seconds = max(
                $completedAt->getTimestamp() - $task->createdAt()->getTimestamp(),
                0
            );

            if ($seconds > 0) {
                $durations[] = $seconds;
            }
        }

        if ($durations === []) {
            return [
                'average_days' => 0.0,
                'median_days' => 0.0,
                'samples' => 0,
            ];
        }

        sort($durations);

        $samples = count($durations);
        $averageSeconds = array_sum($durations) / $samples;
        $middle = intdiv($samples, 2);

        if ($samples % 2 === 0) {
            $medianSeconds = ($durations[$middle - 1] + $durations[$middle]) / 2;
        } else {
            $medianSeconds = $durations[$middle];
        }

        return [
            'average_days' => round($averageSeconds / 86400, 2),
            'median_days' => round($medianSeconds / 86400, 2),
            'samples' => $samples,
        ];
    }

    /**
     * @return array<int, array{task_id: string, title: string, status: string, age_days: float}>
     */
    public function agingWorkInProgress(int $limit = 5): array
    {
        $wipTasks = array_filter(
            $this->tasks,
            static fn (InsightTask $task) => in_array(
                $task->status(),
                [WorkflowStatus::IN_PROGRESS, WorkflowStatus::IN_REVIEW],
                true
            )
        );

        if ($wipTasks === []) {
            return [];
        }

        $now = new DateTimeImmutable('now');

        $aging = array_map(
            static fn (InsightTask $task) => [
                'task_id' => $task->id(),
                'title' => $task->title(),
                'status' => $task->status(),
                'age_days' => round(
                    max($now->getTimestamp() - $task->createdAt()->getTimestamp(), 0) / 86400,
                    1
                ),
            ],
            $wipTasks
        );

        usort(
            $aging,
            static fn (array $a, array $b) => $b['age_days'] <=> $a['age_days']
        );

        return array_slice($aging, 0, $limit);
    }

    private function doneBetween(DateTimeImmutable $start, DateTimeImmutable $end): int
    {
        return count(array_filter(
            $this->tasks,
            static fn (InsightTask $task) => $task->completedAt()
                && $task->completedAt() >= $start
                && $task->completedAt() < $end
        ));
    }

    private function assertMissing(string $taskId): void
    {
        if (isset($this->tasks[$taskId])) {
            throw new InvalidArgumentException("Task {$taskId} already present in project {$this->name}.");
        }
    }

    private function assertPresent(string $taskId): InsightTask
    {
        if (!isset($this->tasks[$taskId])) {
            throw new InvalidArgumentException("Missing task {$taskId} in project {$this->name}.");
        }

        return $this->tasks[$taskId];
    }
}
