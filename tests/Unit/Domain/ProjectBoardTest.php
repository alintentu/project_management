<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\ProjectInsights\DomainEvents;
use App\Domain\ProjectInsights\InsightTask;
use App\Domain\ProjectInsights\ProjectBoard;
use App\Domain\ProjectInsights\WorkflowStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ProjectBoardTest extends TestCase
{
    public function test_cycle_time_metrics_calculates_average_and_median(): void
    {
        $board = new ProjectBoard('1', 'Test Board', new DomainEvents());

        $board->addTask(new InsightTask(
            'A',
            'First task',
            WorkflowStatus::DONE,
            new DateTimeImmutable('-4 days'),
            null,
            new DateTimeImmutable('-1 day')
        ));

        $board->addTask(new InsightTask(
            'B',
            'Second task',
            WorkflowStatus::DONE,
            new DateTimeImmutable('-6 days'),
            null,
            new DateTimeImmutable('-2 days')
        ));

        $board->addTask(new InsightTask(
            'C',
            'Ongoing task',
            WorkflowStatus::IN_PROGRESS,
            new DateTimeImmutable('-3 days')
        ));

        $metrics = $board->cycleTimeMetrics();

        $this->assertSame(2, $metrics['samples']);
        $this->assertEqualsWithDelta(3.5, $metrics['average_days'], 0.01);
        $this->assertEqualsWithDelta(3.5, $metrics['median_days'], 0.01);
    }

    public function test_aging_work_in_progress_orders_by_age_and_limits_results(): void
    {
        $board = new ProjectBoard('1', 'Test Board', new DomainEvents());

        $board->addTask(new InsightTask(
            'A',
            'Recent WIP',
            WorkflowStatus::IN_PROGRESS,
            new DateTimeImmutable('-2 days')
        ));

        $board->addTask(new InsightTask(
            'B',
            'Older review',
            WorkflowStatus::IN_REVIEW,
            new DateTimeImmutable('-5 days')
        ));

        $board->addTask(new InsightTask(
            'C',
            'Completed task',
            WorkflowStatus::DONE,
            new DateTimeImmutable('-10 days'),
            null,
            new DateTimeImmutable('-1 day')
        ));

        $results = $board->agingWorkInProgress(1);

        $this->assertCount(1, $results);
        $this->assertSame('B', $results[0]['task_id']);
        $this->assertGreaterThan(0, $results[0]['age_days']);
    }
}
