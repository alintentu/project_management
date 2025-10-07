<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectFlowDashboardService;
use Illuminate\Http\JsonResponse;

final class ProjectFlowDashboardController extends Controller
{
    public function __invoke(Project $project, ProjectFlowDashboardService $service): JsonResponse
    {
        $snapshot = $service->snapshot($project);

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'insights' => $snapshot,
        ]);
    }
}
