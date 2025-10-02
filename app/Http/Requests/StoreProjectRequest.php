<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('project.create') ?? false;
    }

    public function rules(): array
    {
        $statusOptions = ['planning', 'executie', 'in_verificare', 'finalizat'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('projects', 'code')],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in($statusOptions)],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'actual_start_date' => ['nullable', 'date'],
            'actual_end_date' => ['nullable', 'date', 'after_or_equal:actual_start_date'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated($key, $default);

        $data['status'] = $data['status'] ?? 'planning';

        return $data;
    }
}
