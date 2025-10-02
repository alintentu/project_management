<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus as TaskStatusEnum;
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
            ->withCount(['tasks as completed_tasks_count' => fn ($query) => $query->where('status', TaskStatusEnum::DONE->value)])
            ->withCount('tasks')
            ->select('id', 'name', 'code', 'status', 'planned_start_date', 'planned_end_date', 'actual_start_date', 'actual_end_date')
            ->orderBy('name')
            ->get()
            ->map(function (Project $project) {
                $progress = $project->tasks_count > 0
                    ? round(($project->completed_tasks_count / $project->tasks_count) * 100, 1)
                    : 0;

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'status' => $project->status,
                    'planned_start_date' => optional($project->planned_start_date)->toDateString(),
                    'planned_end_date' => optional($project->planned_end_date)->toDateString(),
                    'actual_start_date' => optional($project->actual_start_date)->toDateString(),
                    'actual_end_date' => optional($project->actual_end_date)->toDateString(),
                    'tasks_count' => $project->tasks_count,
                    'completed_tasks_count' => $project->completed_tasks_count,
                    'progress_percent' => $progress,
                ];
            });

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
                    $children = $buildNode($node->id);

                    $taskPlannedStarts = $node->tasks->pluck('planned_start_date')->filter();
                    $taskPlannedEnds = $node->tasks->pluck('planned_end_date')->filter();
                    $taskProgressValues = $node->tasks->pluck('progress_percent')->filter();

                    $childPlannedStarts = collect($children)->pluck('calculated_planned_start_date')->filter();
                    $childPlannedEnds = collect($children)->pluck('calculated_planned_end_date')->filter();
                    $childProgressValues = collect($children)->pluck('calculated_progress')->filter();

                    $calculatedPlannedStart = collect([$node->planned_start_date])
                        ->merge($taskPlannedStarts)
                        ->merge($childPlannedStarts)
                        ->filter()
                        ->min()?->toDateString();

                    $calculatedPlannedEnd = collect([$node->planned_end_date])
                        ->merge($taskPlannedEnds)
                        ->merge($childPlannedEnds)
                        ->filter()
                        ->max()?->toDateString();

                    $progressCollection = collect($taskProgressValues)
                        ->merge($childProgressValues);

                    $calculatedProgress = $progressCollection->count() > 0
                        ? round($progressCollection->avg(), 1)
                        : null;

                    return [
                        'id' => $node->id,
                        'name' => $node->name,
                        'code' => $node->code,
                        'phase_type' => $node->phase_type,
                        'position' => $node->position,
                        'planned_start_date' => optional($node->planned_start_date)->toDateString(),
                        'planned_end_date' => optional($node->planned_end_date)->toDateString(),
                        'description' => $node->description,
                        'calculated_planned_start_date' => $calculatedPlannedStart,
                        'calculated_planned_end_date' => $calculatedPlannedEnd,
                        'calculated_progress' => $calculatedProgress,
                        'tasks' => $node->tasks->map(fn ($task) => [
                            'id' => $task->id,
                            'title' => $task->title,
                            'status' => $task->status->value,
                            'progress_percent' => $task->progress_percent,
                            'planned_start_date' => optional($task->planned_start_date)->toDateString(),
                            'planned_end_date' => optional($task->planned_end_date)->toDateString(),
                            'assignee' => $task->assignee?->only(['id', 'name']),
                        ])->all(),
                        'children' => $children,
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
