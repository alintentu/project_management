<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectFlowSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ProjectFlowSnapshotHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_project_flow_snapshot_history(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $project = Project::query()->create([
            'name' => 'Snapshot history',
            'code' => 'HIST',
            'status' => 'active',
        ]);

        /** @var ProjectFlowSnapshotService $snapshotService */
        $snapshotService = resolve(ProjectFlowSnapshotService::class);

        $snapshotService->capture($project, Carbon::parse('2025-02-14 12:00:00'));
        $snapshotService->capture($project, Carbon::parse('2025-02-14 13:00:00'));

        $response = $this->actingAs($user)
            ->getJson(route('projects.insights.flow.history', $project));

        $response->assertOk()
            ->assertJson(fn ($json) => $json
                ->where('data.project.id', $project->id)
                ->where('data.project.name', $project->name)
                ->has('data.snapshots', 2, fn ($snapshot) => $snapshot
                    ->where('summary.total', fn ($value) => is_int($value))
                    ->where('captured_at', fn ($value) => is_string($value))
                    ->etc()
                )
            );
    }
}
