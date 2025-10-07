<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TaskStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_transitions_on_create_and_status_change(): void
    {
        $project = Project::query()->create([
            'name' => 'Workflow tracking',
            'code' => 'FLOW',
            'status' => 'active',
        ]);

        $task = Task::factory()->backlog()->create([
            'project_id' => $project->id,
            'title' => 'Draft architecture notes',
        ]);

        $this->assertDatabaseHas('task_status_transitions', [
            'task_id' => $task->id,
            'project_id' => $project->id,
            'from_status' => null,
            'to_status' => TaskStatus::BACKLOG->value,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $task->update([
            'status' => TaskStatus::IN_PROGRESS,
        ]);

        $this->assertDatabaseHas('task_status_transitions', [
            'task_id' => $task->id,
            'project_id' => $project->id,
            'from_status' => TaskStatus::BACKLOG->value,
            'to_status' => TaskStatus::IN_PROGRESS->value,
        ]);

        $this->assertDatabaseHas('task_status_transitions', [
            'task_id' => $task->id,
            'to_status' => TaskStatus::IN_PROGRESS->value,
            'metadata->user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('task_status_transitions', 2);
    }
}
