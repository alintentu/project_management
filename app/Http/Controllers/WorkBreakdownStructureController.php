<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWbsRequest;
use App\Http\Requests\UpdateWbsRequest;
use App\Models\WorkBreakdownStructure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class WorkBreakdownStructureController extends Controller
{
    public function store(StoreWbsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $position = $data['position'] ?? (
            WorkBreakdownStructure::query()
                ->where('project_id', $data['project_id'])
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('position') + 1
        );

        $wbs = WorkBreakdownStructure::create([
            'project_id' => $data['project_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'phase_type' => $data['phase_type'] ?? null,
            'position' => $position,
            'description' => $data['description'] ?? null,
            'planned_start_date' => $data['planned_start_date'] ?? null,
            'planned_end_date' => $data['planned_end_date'] ?? null,
        ]);

        return Redirect::back()->with('flash', [
            'message' => "Structura '{$wbs->name}' a fost creată.",
        ]);
    }

    public function update(UpdateWbsRequest $request, WorkBreakdownStructure $wbs): RedirectResponse
    {
        $data = $request->validated();

        $wbs->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? $wbs->code,
            'phase_type' => $data['phase_type'] ?? $wbs->phase_type,
            'position' => $data['position'] ?? $wbs->position,
            'description' => $data['description'] ?? $wbs->description,
            'planned_start_date' => $data['planned_start_date'] ?? $wbs->planned_start_date,
            'planned_end_date' => $data['planned_end_date'] ?? $wbs->planned_end_date,
        ]);

        return Redirect::back()->with('flash', [
            'message' => "Structura '{$wbs->name}' a fost actualizată.",
        ]);
    }

    public function destroy(WorkBreakdownStructure $wbs): RedirectResponse
    {
        $name = $wbs->name;
        $wbs->delete();

        return Redirect::back()->with('flash', [
            'message' => "Structura '{$name}' a fost ștearsă.",
        ]);
    }
}
