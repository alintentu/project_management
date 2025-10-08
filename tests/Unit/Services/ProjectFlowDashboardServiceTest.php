<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskStatusTransition;
use App\Services\ProjectFlowDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ProjectFlowDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_synchronizes_tasks_and_exposes_metrics(): void
    {
        $project = Project::query()->create([
            'name' => 'Flow sync',
            'code' => 'FLOW',
            'status' => 'active',
        ]);

        /** @var Task $wipTask */
        $wipTask = Task::factory()->state([
            'status' => TaskStatus::IN_PROGRESS->value,
            'created_at' => Carbon::now()->subDays(4),
        ])->create([
            'project_id' => $project->id,
            'title' => 'Refine backlog',
        ]);

        /** @var Task $completedTask */
        $completedTask = Task::factory()->done()->create([
            'project_id' => $project->id,
            'title' => 'Finalize onboarding',
            'created_at' => Carbon::now()->subDays(8),
            'actual_end_date' => Carbon::now()->subDays(1),
        ]);

        TaskStatusTransition::query()->create([
            'task_id' => $completedTask->id,
            'project_id' => $project->id,
            'from_status' => TaskStatus::IN_PROGRESS->value,
            'to_status' => TaskStatus::DONE->value,
            'occurred_at' => Carbon::now()->subDays(1),
        ]);

        // Invalid transition should be ignored by the service.
        TaskStatusTransition::query()->create([
            'task_id' => $completedTask->id,
            'project_id' => $project->id,
            'from_status' => TaskStatus::DONE->value,
            'to_status' => 'archived',
            'occurred_at' => Carbon::now()->subHours(12),
        ]);

        $service = resolve(ProjectFlowDashboardService::class);

        $snapshot = $service->snapshot($project);
        $summary = $snapshot['summary'];

        $this->assertSame(2, $summary['total']);
        $this->assertSame(1, $summary['status']['in_progress']);
        $this->assertSame(1, $summary['status']['done']);

        $this->assertSame(1, $summary['cycle_time']['samples']);
        $this->assertNotNull($summary['cycle_time']['average_days']);
        $this->assertGreaterThan(0, $summary['cycle_time']['average_days']);

        $this->assertNotNull($snapshot['meta']['last_transition_at']);

        $agingWip = $summary['aging_wip'];
        $this->assertCount(1, $agingWip);
        $this->assertSame((string) $wipTask->id, $agingWip[0]['task_id']);
        $this->assertGreaterThanOrEqual(0, $agingWip[0]['age_days']);
    }
}
