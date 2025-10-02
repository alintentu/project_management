<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskScheduleRequest;
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
        $task->load(['assignee', 'media']);

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
                'meta' => $status->theme(),
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
                'meta' => $task->status->theme(),
                'attachments' => $task->getMedia('attachments')->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'size_label' => $this->humanReadableSize($media->size),
                    'url' => $media->getUrl(),
                ])->all(),
            ],
            'users' => $users,
            'statuses' => $statuses,
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $statusValue = $data['status'] ?? TaskStatus::BACKLOG->value;
        $status = TaskStatus::from($statusValue);

        $position = Task::query()
            ->where('status', $status->value)
            ->max('position');

        $task = Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status->value,
            'assigned_to_id' => $data['assigned_to_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'position' => ($position ?? 0) + 1,
        ]);

        return Redirect::route('tasks.show', $task)
            ->with('flash', [
                'message' => 'Task creat cu succes.',
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

    public function updateSchedule(UpdateTaskScheduleRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();

        $task->fill($data);

        if ($task->planned_start_date && $task->planned_end_date) {
            $task->planned_duration_days = $task->planned_start_date->diffInDays($task->planned_end_date) + 1;
        }

        if ($task->actual_start_date && $task->actual_end_date) {
            $task->actual_duration_days = $task->actual_start_date->diffInDays($task->actual_end_date) + 1;
        }

        $task->save();

        return Redirect::back()->with('flash', [
            'message' => 'Programarea taskului a fost actualizată.',
        ]);
    }

    private function humanReadableSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);
        $factor = max(0, min($factor, count($units) - 1));

        return sprintf('%.2f %s', $bytes / (1024 ** $factor), $units[$factor]);
    }
}
