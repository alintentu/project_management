<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\UpdateTaskOrderRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class TaskOrderController extends Controller
{
    public function __invoke(UpdateTaskOrderRequest $request): RedirectResponse
    {
        $columns = $request->validated('columns');

        DB::transaction(function () use ($columns) {
            foreach ($columns as $column) {
                $status = TaskStatus::from($column['status']);
                $taskIds = $column['task_ids'] ?? [];

                foreach ($taskIds as $index => $taskId) {
                    Task::query()
                        ->whereKey($taskId)
                        ->update([
                            'status' => $status->value,
                            'position' => $index + 1,
                        ]);
                }
            }
        });

        return Redirect::back();
    }
}
