<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DashboardFlowInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_flow_insights(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $project = Project::query()->create([
            'name' => 'Alpha CRM',
            'code' => 'ALPHA',
            'status' => 'active',
        ]);

        Task::factory()->backlog()->create([
            'project_id' => $project->id,
            'title' => 'Draft requirements',
            'created_at' => Carbon::now()->subDays(9),
        ]);

        Task::factory()->state([
            'status' => TaskStatus::IN_REVIEW->value,
            'metadata' => ['review_notes' => 'QA pending'],
        ])->create([
            'project_id' => $project->id,
            'title' => 'Implement authentication',
            'created_at' => Carbon::now()->subDays(5),
        ]);

        Task::factory()->done()->create([
            'project_id' => $project->id,
            'title' => 'Ship landing page',
            'created_at' => Carbon::now()->subDays(7),
            'actual_end_date' => Carbon::now()->subDay(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('projects', 1)
            ->where('projects.0.id', $project->id)
            ->where('initialProjectId', $project->id)
            ->where('flowInsights.summary.total', 3)
            ->where('flowInsights.summary.status.in_review', 1)
            ->where('flowInsights.summary.cycle_time.samples', 1)
            ->where('flowInsights.summary.aging_wip', fn ($value) => is_iterable($value))
            ->has('flowInsights.alerts', fn (Assert $alerts) => $alerts
                ->where('0.type', 'aging_wip_tasks')
                ->where('0.severity', 'warning')
                ->etc()
            )
            ->has('flowInsights.trend.points', fn (Assert $points) => $points
                ->etc()
            )
            ->where('flowInsights.focus', 'Finalize review for 1 tasks before starting new work.')
            ->where('flowInsights.meta.generated_at', fn ($value) => is_string($value) && $value !== '')
            ->where('flowInsights.meta.project_updated_at', fn ($value) => $value === null || is_string($value))
            ->etc()
        );
    }
}
