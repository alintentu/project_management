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
