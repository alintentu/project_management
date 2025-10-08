<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class CaptureProjectFlowSnapshotsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_captures_snapshots_for_projects(): void
    {
        $project = Project::query()->create([
            'name' => 'Command Project',
            'code' => 'CMD',
            'status' => 'active',
        ]);

        Task::factory()->state([
            'status' => TaskStatus::IN_PROGRESS->value,
            'created_at' => Carbon::now()->subHours(5),
        ])->create([
            'project_id' => $project->id,
            'title' => 'Critical task',
        ]);

        Artisan::call('flow:snapshots:capture', [
            '--project' => [$project->id],
            '--backfill-hours' => 1,
        ]);

        $this->assertDatabaseCount('project_flow_snapshots', 2);
        $this->assertDatabaseHas('project_flow_snapshots', [
            'project_id' => $project->id,
        ]);
    }
}
