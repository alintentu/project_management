<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function show(Task $task): Response
    {
        $task->load('assignee');

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

        return Inertia::render('Tasks/Show', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status->value,
                'status_label' => $task->status->label(),
                'due_date' => $task->due_date?->toDateString(),
                'assigned_to_id' => $task->assigned_to_id,
                'assignee' => $task->assignee?->only(['id', 'name']),
                'created_at' => $task->created_at?->toDateTimeString(),
                'updated_at' => $task->updated_at?->toDateTimeString(),
            ],
            'users' => $users,
            'statuses' => $statuses,
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();

        if (array_key_exists('due_date', $data) && empty($data['due_date'])) {
            $data['due_date'] = null;
        }

        $task->update($data);

        return Redirect::route('dashboard')
            ->with('flash', [
                'message' => 'Task actualizat cu succes.',
            ]);
    }
}
