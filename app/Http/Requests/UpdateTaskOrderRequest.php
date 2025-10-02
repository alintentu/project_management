<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('task.update') ?? true;
    }

    public function rules(): array
    {
        return [
            'columns' => ['required', 'array'],
            'columns.*.status' => ['required', Rule::in(array_map(fn (TaskStatus $status) => $status->value, TaskStatus::cases()))],
            'columns.*.task_ids' => ['sometimes', 'array'],
            'columns.*.task_ids.*' => ['integer', Rule::exists('tasks', 'id')],
        ];
    }
}
