<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Normalize empty strings to null to avoid invalid dates being persisted.
        $payload = collect($data)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();

        $project = Project::create($payload);

        return redirect()
            ->route('planning', ['project_id' => $project->id])
            ->with('flash', ['message' => 'Proiect creat cu succes.']);
    }
}
