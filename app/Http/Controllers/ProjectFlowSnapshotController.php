<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFlowSnapshot;
use Illuminate\Http\JsonResponse;

final class ProjectFlowSnapshotController extends Controller
{
    public function index(Project $project): JsonResponse
    {
        $snapshots = ProjectFlowSnapshot::query()
            ->where('project_id', $project->getKey())
            ->orderByDesc('captured_at')
            ->limit(200)
            ->get()
            ->map(fn (ProjectFlowSnapshot $snapshot) => [
                'captured_at' => $snapshot->captured_at?->toAtomString(),
                'summary' => $snapshot->summary ?? [],
                'alerts' => $snapshot->alerts ?? [],
                'focus' => $snapshot->focus,
                'meta' => $snapshot->meta ?? [],
            ]);

        return response()->json([
            'data' => [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
                'snapshots' => $snapshots,
            ],
        ]);
    }
}
