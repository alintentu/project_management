<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class TaskStatusController extends Controller
{
    public function __invoke(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $task->update($request->validated());

        return Redirect::back();
    }
}
