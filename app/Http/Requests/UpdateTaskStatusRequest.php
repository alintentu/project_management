<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $statusValues = collect(TaskStatus::cases())
            ->map(fn (TaskStatus $status) => $status->value)
            ->all();

        return [
            'status' => ['required', Rule::in($statusValues)],
        ];
    }
}
