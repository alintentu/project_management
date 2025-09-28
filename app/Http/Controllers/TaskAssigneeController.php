<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTaskAssigneeRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class TaskAssigneeController extends Controller
{
    public function __invoke(UpdateTaskAssigneeRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();

        $task->update([
            'assigned_to_id' => $data['assigned_to_id'] ?? null,
        ]);

        return Redirect::back();
    }
}
