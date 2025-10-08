<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\ProjectInsights\DomainEvents;
use App\Domain\ProjectInsights\FlowAlertEvaluator;
use App\Domain\ProjectInsights\InsightTask;
use App\Domain\ProjectInsights\ProjectBoard;
use App\Domain\ProjectInsights\WorkflowStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FlowAlertEvaluatorTest extends TestCase
{
    public function test_it_flags_cycle_time_and_aging_wip(): void
    {
        $board = new ProjectBoard('1', 'Board', new DomainEvents());

        $board->addTask(new InsightTask(
            '1',
            'Long running task',
            WorkflowStatus::IN_PROGRESS,
            new DateTimeImmutable('-10 days')
        ));

        $board->addTask(new InsightTask(
            '2',
            'Completed task',
            WorkflowStatus::DONE,
            new DateTimeImmutable('-12 days'),
            null,
            new DateTimeImmutable('-1 day')
        ));

        $evaluator = new FlowAlertEvaluator(cycleTimeThresholdDays: 5, agingThresholdDays: 3, wipLimit: 2);

        $alerts = $evaluator->evaluate($board);

        $this->assertNotEmpty($alerts);
        $this->assertSame('cycle_time_high', $alerts[0]->type());
        $this->assertSame('aging_wip_tasks', $alerts[1]->type());
    }

    public function test_it_flags_empty_backlog_and_wip_limit(): void
    {
        $board = new ProjectBoard('2', 'WIP Board', new DomainEvents());

        $board->addTask(new InsightTask(
            '1',
            'In progress A',
            WorkflowStatus::IN_PROGRESS,
            new DateTimeImmutable('-2 days')
        ));
        $board->addTask(new InsightTask(
            '2',
            'In progress B',
            WorkflowStatus::IN_REVIEW,
            new DateTimeImmutable('-1 day')
        ));

        $evaluator = new FlowAlertEvaluator(cycleTimeThresholdDays: 10, agingThresholdDays: 10, wipLimit: 2);

        $alerts = $evaluator->evaluate($board);

        $types = array_map(static fn ($alert) => $alert->type(), $alerts);

        $this->assertContains('backlog_empty', $types);
        $this->assertContains('wip_limit_breached', $types);
    }
}
