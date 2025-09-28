<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $users = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->all();

        $statuses = collect(TaskStatus::cases())
            ->map(fn (TaskStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ])
            ->all();

        return Inertia::render('Dashboard', [
            'sections' => $this->sections(),
            'users' => $users,
            'statuses' => $statuses,
        ]);
    }

    private function sections(): array
    {
        return collect(TaskStatus::cases())
            ->map(fn (TaskStatus $status) => [
                'key' => $status->value,
                'title' => $status->label(),
                'tasks' => $this->tasksByStatus($status),
            ])
            ->all();
    }

    private function tasksByStatus(TaskStatus $status): array
    {
        return Task::query()
            ->status($status)
            ->with('assignee:id,name')
            ->orderBy('position')
            ->orderBy('created_at')
            ->get()
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status->value,
                'status_label' => $task->status->label(),
                'due_date' => $task->due_date?->toDateString(),
                'assignee' => $task->assignee?->only(['id', 'name']),
                'assignee_id' => $task->assigned_to_id,
            ])
            ->all();
    }
}
