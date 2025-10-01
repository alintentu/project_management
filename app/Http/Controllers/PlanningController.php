<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\WorkBreakdownStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class PlanningController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $projects = Project::query()
            ->select('id', 'name', 'code', 'status')
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'status' => $project->status,
            ]);

        $selectedProjectId = $request->query('project_id', $projects->first()['id'] ?? null);

        $project = $selectedProjectId
            ? Project::query()
                ->with([
                    'resources' => fn ($query) => $query->orderBy('name'),
                    'siteLogs' => fn ($query) => $query->orderByDesc('log_date')->limit(7),
                ])
                ->find($selectedProjectId)
            : null;

        $wbsTree = [];
        $resources = [];
        $siteLogs = [];
        $timeline = [];

        if ($project) {
            $wbsItems = WorkBreakdownStructure::query()
                ->where('project_id', $project->id)
                ->with([
                    'tasks' => fn ($query) => $query
                        ->orderBy('position')
                        ->with('assignee:id,name')
                        ->select(
                            'id',
                            'title',
                            'status',
                            'assigned_to_id',
                            'planned_start_date',
                            'planned_end_date',
                            'actual_start_date',
                            'actual_end_date',
                            'progress_percent',
                            'position',
                            'wbs_id'
                        ),
                ])
                ->orderBy('position')
                ->get();

            $wbsTree = $this->buildWbsTree($wbsItems);

            $resources = $project->resources->map(fn ($resource) => [
                'id' => $resource->id,
                'name' => $resource->name,
                'type' => $resource->resource_type,
                'capacity' => $resource->capacity,
                'cost_rate' => $resource->cost_rate,
                'cost_rate_unit' => $resource->cost_rate_unit,
                'is_active' => $resource->is_active,
            ]);

            $siteLogs = $project->siteLogs->map(fn ($log) => [
                'id' => $log->id,
                'date' => $log->log_date?->toDateString(),
                'weather' => $log->weather,
                'temperature' => $log->temperature,
                'progress_percent' => $log->progress_percent,
                'summary' => $log->summary,
                'manpower_count' => $log->manpower_count,
            ]);

            $timeline = $this->buildTimeline($project, $wbsItems);
        }

        return Inertia::render('Planning/Index', [
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
            'wbsTree' => $wbsTree,
            'resources' => $resources,
            'siteLogs' => $siteLogs,
            'timeline' => $timeline,
        ]);
    }

    private function buildWbsTree(Collection $items): array
    {
        $grouped = $items->groupBy('parent_id');

        $buildNode = function ($parentId) use (&$buildNode, $grouped) {
            return $grouped->get($parentId, collect())
                ->map(function (WorkBreakdownStructure $node) use (&$buildNode) {
                    return [
                        'id' => $node->id,
                        'name' => $node->name,
                        'code' => $node->code,
                        'phase_type' => $node->phase_type,
                        'position' => $node->position,
                        'planned_start_date' => optional($node->planned_start_date)->toDateString(),
                        'planned_end_date' => optional($node->planned_end_date)->toDateString(),
                        'description' => $node->description,
                        'tasks' => $node->tasks->map(fn ($task) => [
                            'id' => $task->id,
                            'title' => $task->title,
                            'status' => $task->status->value,
                            'progress_percent' => $task->progress_percent,
                            'planned_start_date' => optional($task->planned_start_date)->toDateString(),
                            'planned_end_date' => optional($task->planned_end_date)->toDateString(),
                            'assignee' => $task->assignee?->only(['id', 'name']),
                        ])->all(),
                        'children' => $buildNode($node->id),
                    ];
                })
                ->values()
                ->all();
        };

        return $buildNode(null);
    }

    private function buildTimeline(Project $project, Collection $wbsItems): array
    {
        $tasks = $project->tasks()
            ->select(
                'id',
                'title',
                'wbs_id',
                'planned_start_date',
                'planned_end_date',
                'actual_start_date',
                'actual_end_date',
                'progress_percent'
            )
            ->orderBy('planned_start_date')
            ->get();

        return $tasks->map(function (Task $task) use ($wbsItems) {
            $wbsName = optional($wbsItems->firstWhere('id', $task->wbs_id))->name;

            return [
                'id' => $task->id,
                'title' => $task->title,
                'wbs_name' => $wbsName,
                'planned_start_date' => optional($task->planned_start_date)->toDateString(),
                'planned_end_date' => optional($task->planned_end_date)->toDateString(),
                'actual_start_date' => optional($task->actual_start_date)->toDateString(),
                'actual_end_date' => optional($task->actual_end_date)->toDateString(),
                'progress_percent' => $task->progress_percent,
            ];
        })->all();
    }
}
