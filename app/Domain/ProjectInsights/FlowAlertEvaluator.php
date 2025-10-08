<?php

declare(strict_types=1);

namespace App\Domain\ProjectInsights;

/**
 * Evaluates a project board and surfaces actionable flow alerts.
 */
final class FlowAlertEvaluator
{
    public function __construct(
        private readonly int $cycleTimeThresholdDays = 7,
        private readonly int $agingThresholdDays = 5,
        private readonly int $wipLimit = 5
    ) {
    }

    /**
     * @return array<int, FlowAlert>
     */
    public function evaluate(ProjectBoard $board): array
    {
        $alerts = [];

        $statusCounts = $this->statusCounts($board);
        $totalTasks = array_sum($statusCounts);

        $cycleTime = $board->cycleTimeMetrics();

        if ($cycleTime['samples'] > 0 && $cycleTime['average_days'] > $this->cycleTimeThresholdDays) {
            $alerts[] = new FlowAlert(
                'cycle_time_high',
                sprintf(
                    'Average cycle time is %sd which exceeds the %sd threshold.',
                    $this->formatDays($cycleTime['average_days']),
                    $this->cycleTimeThresholdDays
                ),
                FlowAlert::SEVERITY_WARNING,
                [
                    'average_days' => $cycleTime['average_days'],
                    'median_days' => $cycleTime['median_days'],
                    'threshold_days' => $this->cycleTimeThresholdDays,
                    'samples' => $cycleTime['samples'],
                ]
            );
        }

        $agingWip = $board->agingWorkInProgress();
        $stalled = array_filter(
            $agingWip,
            fn (array $item) => $item['age_days'] >= $this->agingThresholdDays
        );

        if ($stalled !== []) {
            $alerts[] = new FlowAlert(
                'aging_wip_tasks',
                sprintf(
                    'Work in progress ageing over %sd: %s.',
                    $this->agingThresholdDays,
                    implode(', ', array_map(
                        fn (array $item) => sprintf(
                            '%s (%sd)',
                            $item['title'],
                            $this->formatDays($item['age_days'])
                        ),
                        array_slice($stalled, 0, 5)
                    ))
                ),
                FlowAlert::SEVERITY_WARNING,
                [
                    'threshold_days' => $this->agingThresholdDays,
                    'tasks' => array_values($stalled),
                ]
            );
        }

        if ($totalTasks > 0 && ($statusCounts[WorkflowStatus::BACKLOG] ?? 0) === 0) {
            $alerts[] = new FlowAlert(
                'backlog_empty',
                'Backlog is empty; replenish upcoming work.',
                FlowAlert::SEVERITY_INFO,
                [
                    'status_counts' => $statusCounts,
                ]
            );
        }

        $wipBreaches = $board->wipLimitBreaches($this->wipLimit);

        if (count($wipBreaches) >= $this->wipLimit) {
            $alerts[] = new FlowAlert(
                'wip_limit_breached',
                sprintf(
                    'WIP limit of %s reached; focus on finishing tasks %s.',
                    $this->wipLimit,
                    implode(', ', $wipBreaches)
                ),
                FlowAlert::SEVERITY_WARNING,
                [
                    'limit' => $this->wipLimit,
                    'task_ids' => $wipBreaches,
                ]
            );
        }

        return $alerts;
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(ProjectBoard $board): array
    {
        $buckets = array_fill_keys(WorkflowStatus::all(), 0);

        foreach ($board->tasks() as $task) {
            $buckets[$task->status()]++;
        }

        return $buckets;
    }

    private function formatDays(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }
}
