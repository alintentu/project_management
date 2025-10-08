<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

final class ProjectFlowDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_flow_insights_for_a_project(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $project = Project::query()->create([
            'name' => 'Alpha CRM',
            'code' => 'ALPHA',
            'description' => 'Internal platform upgrade.',
            'status' => 'active',
        ]);

        Task::factory()->backlog()->create([
            'project_id' => $project->id,
            'title' => 'Draft requirements',
            'created_at' => Carbon::now()->subDays(10),
        ]);

        Task::factory()->state([
            'status' => TaskStatus::IN_REVIEW->value,
            'metadata' => ['review_notes' => 'Awaiting QA sign-off'],
        ])->create([
            'project_id' => $project->id,
            'title' => 'Implement authentication',
            'created_at' => Carbon::now()->subDays(6),
        ]);

        Task::factory()->done()->create([
            'project_id' => $project->id,
            'title' => 'Ship landing page',
            'created_at' => Carbon::now()->subDays(8),
            'actual_end_date' => Carbon::now()->subDays(2),
        ]);

        $response = $this->getJson(route('projects.insights.flow', $project));

        $response->assertOk();

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertNotNull($cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);

        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('project.id', $project->id)
            ->where('project.name', $project->name)
            ->where('insights.summary.total', 3)
            ->has('insights.summary.status', fn (AssertableJson $status) => $status
                ->where('backlog', 1)
                ->where('in_progress', 0)
                ->where('in_review', 1)
                ->where('done', 1)
            )
            ->has('insights.summary.velocity')
            ->where('insights.summary.review_ratio', 1 / 3)
            ->has('insights.summary.cycle_time', fn (AssertableJson $cycle) => $cycle
                ->where('samples', 1)
                ->where('average_days', fn ($value) => is_float($value))
                ->where('median_days', fn ($value) => is_float($value))
            )
            ->has('insights.summary.aging_wip', fn (AssertableJson $aging) => $aging
                ->etc()
            )
            ->where('insights.focus', 'Finalize review for 1 tasks before starting new work.')
            ->where('insights.meta.generated_at', fn ($value) => is_string($value) && $value !== '')
            ->where('insights.meta.project_updated_at', fn ($value) => $value === null || is_string($value))
            ->where('insights.meta.last_transition_at', fn ($value) => $value === null || is_string($value))
            ->etc()
        );
    }
}
