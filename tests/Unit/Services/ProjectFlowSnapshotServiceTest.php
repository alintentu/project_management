<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectFlowSnapshot;
use App\Services\ProjectFlowSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ProjectFlowSnapshotServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_flow_snapshot_payload(): void
    {
        $project = Project::query()->create([
            'name' => 'Snapshot Project',
            'code' => 'SNAP',
            'status' => 'active',
        ]);

        Task::factory()->backlog()->create([
            'project_id' => $project->id,
            'title' => 'Plan work',
        ]);

        Task::factory()->state([
            'status' => TaskStatus::IN_PROGRESS->value,
            'created_at' => Carbon::now()->subDays(3),
        ])->create([
            'project_id' => $project->id,
            'title' => 'Implement feature',
        ]);

        /** @var ProjectFlowSnapshotService $service */
        $service = resolve(ProjectFlowSnapshotService::class);

        $capturedAt = Carbon::now()->startOfHour();
        $snapshot = $service->capture($project, $capturedAt);

        $this->assertInstanceOf(ProjectFlowSnapshot::class, $snapshot);
        $this->assertTrue($snapshot->captured_at->equalTo($capturedAt));
        $this->assertNotEmpty($snapshot->summary);
        $this->assertSame($snapshot->project_id, $project->id);

        $this->assertDatabaseHas('project_flow_snapshots', [
            'project_id' => $project->id,
            'captured_at' => $capturedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
