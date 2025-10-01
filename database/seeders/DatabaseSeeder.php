<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\ResourceUnit;
use App\Models\SiteLog;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkBreakdownStructure;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
				$this->call([
				    PermissionsSeeder::class,
				]);


        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['admin']);

        $projectsSeed = [
            [
                'code' => 'PRJ-001',
                'name' => 'Clădire birouri – București',
                'status' => 'executie',
                'description' => 'Turn de birouri cu 10 niveluri și parcare subterană.',
                'planned_start' => Carbon::now()->subMonths(3)->startOfMonth(),
                'planned_end' => Carbon::now()->addMonths(3)->endOfMonth(),
                'actual_start' => Carbon::now()->subMonths(2)->startOfMonth(),
                'actual_end' => null,
                'wbs' => [
                    [
                        'code' => 'PRJ-001-STR',
                        'name' => 'Structură',
                        'phase_type' => 'structura',
                        'planned_start' => Carbon::now()->subMonths(3)->startOfMonth(),
                        'planned_end' => Carbon::now()->subMonths(1)->endOfMonth(),
                        'tasks' => [
                            [
                                'title' => 'Săpături generale',
                                'status' => TaskStatus::DONE->value,
                                'planned_start' => Carbon::now()->subMonths(3)->addDays(1),
                                'planned_end' => Carbon::now()->subMonths(3)->addDays(10),
                                'actual_start' => Carbon::now()->subMonths(3)->addDays(1),
                                'actual_end' => Carbon::now()->subMonths(3)->addDays(9),
                                'progress' => 100,
                            ],
                            [
                                'title' => 'Radier general',
                                'status' => TaskStatus::DONE->value,
                                'planned_start' => Carbon::now()->subMonths(3)->addDays(12),
                                'planned_end' => Carbon::now()->subMonths(2)->addDays(5),
                                'actual_start' => Carbon::now()->subMonths(3)->addDays(14),
                                'actual_end' => Carbon::now()->subMonths(2)->addDays(2),
                                'progress' => 100,
                            ],
                            [
                                'title' => 'Stâlpi nivel 2',
                                'status' => TaskStatus::IN_PROGRESS->value,
                                'planned_start' => Carbon::now()->subMonths(2)->addDays(7),
                                'planned_end' => Carbon::now()->subMonths(1)->addDays(15),
                                'actual_start' => Carbon::now()->subMonths(2)->addDays(9),
                                'actual_end' => null,
                                'progress' => 70,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PRJ-001-INS',
                        'name' => 'Instalații',
                        'phase_type' => 'instalatii',
                        'planned_start' => Carbon::now()->subWeeks(6),
                        'planned_end' => Carbon::now()->addMonths(1),
                        'tasks' => [
                            [
                                'title' => 'Trasee HVAC nivel 1',
                                'status' => TaskStatus::IN_PROGRESS->value,
                                'planned_start' => Carbon::now()->subWeeks(6),
                                'planned_end' => Carbon::now()->subWeeks(2),
                                'actual_start' => Carbon::now()->subWeeks(5),
                                'actual_end' => null,
                                'progress' => 45,
                            ],
                            [
                                'title' => 'Tubulaturi sanitare verticale',
                                'status' => TaskStatus::IN_REVIEW->value,
                                'planned_start' => Carbon::now()->subWeeks(3),
                                'planned_end' => Carbon::now()->addWeeks(1),
                                'actual_start' => Carbon::now()->subWeeks(3),
                                'actual_end' => null,
                                'progress' => 60,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PRJ-001-FIN',
                        'name' => 'Finisaje',
                        'phase_type' => 'finisaje',
                        'planned_start' => Carbon::now()->addWeeks(2),
                        'planned_end' => Carbon::now()->addMonths(2),
                        'tasks' => [
                            [
                                'title' => 'Compartimentări gips-carton',
                                'status' => TaskStatus::BACKLOG->value,
                                'planned_start' => Carbon::now()->addWeeks(2),
                                'planned_end' => Carbon::now()->addMonths(1),
                                'actual_start' => null,
                                'actual_end' => null,
                                'progress' => 0,
                            ],
                            [
                                'title' => 'Tencuieli decorative fațadă',
                                'status' => TaskStatus::BACKLOG->value,
                                'planned_start' => Carbon::now()->addMonths(1),
                                'planned_end' => Carbon::now()->addMonths(2),
                                'actual_start' => null,
                                'actual_end' => null,
                                'progress' => 0,
                            ],
                        ],
                    ],
                ],
                'resources' => [
                    [
                        'code' => 'RES-STR-CREW',
                        'name' => 'Echipa Structură',
                        'resource_type' => 'crew',
                        'capacity' => 14,
                        'cost_rate' => 1800,
                        'cost_rate_unit' => 'day',
                        'tasks' => [
                            ['title' => 'Săpături generale', 'allocation' => 100, 'planned_hours' => 120],
                            ['title' => 'Radier general', 'allocation' => 90, 'planned_hours' => 160],
                            ['title' => 'Stâlpi nivel 2', 'allocation' => 80, 'planned_hours' => 200],
                        ],
                    ],
                    [
                        'code' => 'RES-TOWER-CRANE',
                        'name' => 'Macara turn Liebherr 420',
                        'resource_type' => 'equipment',
                        'capacity' => 1,
                        'cost_rate' => 2000,
                        'cost_rate_unit' => 'day',
                        'tasks' => [
                            ['title' => 'Radier general', 'allocation' => 50],
                            ['title' => 'Stâlpi nivel 2', 'allocation' => 70],
                        ],
                    ],
                ],
                'site_logs' => [
                    [
                        'date' => Carbon::now()->subWeeks(2)->startOfWeek(),
                        'weather' => 'Însorit',
                        'temperature' => '24°C',
                        'manpower_count' => 32,
                        'progress_percent' => 32,
                        'summary' => 'Montaj armături și cofraje pentru etajul 2.',
                        'issues' => 'Întârziere beton pompă zona B.',
                        'task_progress' => [
                            ['title' => 'Stâlpi nivel 2', 'progress' => 45, 'notes' => 'Cofraj 80% complet.'],
                        ],
                    ],
                    [
                        'date' => Carbon::now()->subWeek(),
                        'weather' => 'Noros',
                        'temperature' => '18°C',
                        'manpower_count' => 38,
                        'progress_percent' => 38,
                        'summary' => 'Ridicare stâlpi și instalare primii stâlpi metalici pentru fațadă.',
                        'issues' => 'Verificare suplimentară carote beton.',
                        'task_progress' => [
                            ['title' => 'Stâlpi nivel 2', 'progress' => 65],
                            ['title' => 'Trasee HVAC nivel 1', 'progress' => 20],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'PRJ-002',
                'name' => 'Complex rezidențial – Cluj',
                'status' => 'planning',
                'description' => 'Ansamblu rezidențial cu 3 blocuri P+8 și spații comerciale.',
                'planned_start' => Carbon::now()->addWeeks(1),
                'planned_end' => Carbon::now()->addMonths(12),
                'actual_start' => null,
                'actual_end' => null,
                'wbs' => [
                    [
                        'code' => 'PRJ-002-PLAN',
                        'name' => 'Planificare & proiectare',
                        'phase_type' => 'planificare',
                        'planned_start' => Carbon::now()->addWeeks(1),
                        'planned_end' => Carbon::now()->addMonths(2),
                        'tasks' => [
                            [
                                'title' => 'Concept arhitectural',
                                'status' => TaskStatus::BACKLOG->value,
                                'planned_start' => Carbon::now()->addWeeks(1),
                                'planned_end' => Carbon::now()->addWeeks(5),
                                'progress' => 0,
                            ],
                            [
                                'title' => 'Proiect tehnic instalații',
                                'status' => TaskStatus::BACKLOG->value,
                                'planned_start' => Carbon::now()->addWeeks(4),
                                'planned_end' => Carbon::now()->addMonths(2),
                                'progress' => 0,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PRJ-002-ACH',
                        'name' => 'Achiziții & contractare',
                        'phase_type' => 'achizitii',
                        'planned_start' => Carbon::now()->addMonths(2),
                        'planned_end' => Carbon::now()->addMonths(4),
                        'tasks' => [
                            [
                                'title' => 'Licitație antreprenor general',
                                'status' => TaskStatus::BACKLOG->value,
                                'planned_start' => Carbon::now()->addMonths(2),
                                'planned_end' => Carbon::now()->addMonths(3),
                                'progress' => 0,
                            ],
                            [
                                'title' => 'Contractare utilități',
                                'status' => TaskStatus::BACKLOG->value,
                                'planned_start' => Carbon::now()->addMonths(3),
                                'planned_end' => Carbon::now()->addMonths(4),
                                'progress' => 0,
                            ],
                        ],
                    ],
                ],
                'resources' => [
                    [
                        'code' => 'RES-PLAN-TEAM',
                        'name' => 'Echipa Proiectare',
                        'resource_type' => 'crew',
                        'capacity' => 6,
                        'cost_rate' => 900,
                        'cost_rate_unit' => 'day',
                        'tasks' => [
                            ['title' => 'Concept arhitectural', 'allocation' => 100],
                            ['title' => 'Proiect tehnic instalații', 'allocation' => 70],
                        ],
                    ],
                ],
                'site_logs' => [],
            ],
            [
                'code' => 'PRJ-003',
                'name' => 'Extindere hală logistică – Timișoara',
                'status' => 'finalizat',
                'description' => 'Extindere spațiu depozitare și modernizare platformă logistică.',
                'planned_start' => Carbon::now()->subMonths(6)->startOfMonth(),
                'planned_end' => Carbon::now()->subMonth()->endOfMonth(),
                'actual_start' => Carbon::now()->subMonths(6)->startOfMonth(),
                'actual_end' => Carbon::now()->subMonth()->subDays(3),
                'wbs' => [
                    [
                        'code' => 'PRJ-003-EXT',
                        'name' => 'Extindere structură',
                        'phase_type' => 'structura',
                        'planned_start' => Carbon::now()->subMonths(6)->addDays(5),
                        'planned_end' => Carbon::now()->subMonths(3),
                        'tasks' => [
                            [
                                'title' => 'Montaj prefabricate',
                                'status' => TaskStatus::DONE->value,
                                'planned_start' => Carbon::now()->subMonths(6)->addDays(5),
                                'planned_end' => Carbon::now()->subMonths(5)->endOfMonth(),
                                'actual_start' => Carbon::now()->subMonths(6)->addDays(5),
                                'actual_end' => Carbon::now()->subMonths(5)->addDays(20),
                                'progress' => 100,
                            ],
                            [
                                'title' => 'Acoperiș metalic',
                                'status' => TaskStatus::DONE->value,
                                'planned_start' => Carbon::now()->subMonths(5)->addDays(5),
                                'planned_end' => Carbon::now()->subMonths(4)->endOfMonth(),
                                'actual_start' => Carbon::now()->subMonths(5)->addDays(7),
                                'actual_end' => Carbon::now()->subMonths(4)->addDays(15),
                                'progress' => 100,
                            ],
                        ],
                    ],
                    [
                        'code' => 'PRJ-003-FIN',
                        'name' => 'Finisaje & utilități',
                        'phase_type' => 'instalatii',
                        'planned_start' => Carbon::now()->subMonths(4),
                        'planned_end' => Carbon::now()->subMonth()->endOfMonth(),
                        'tasks' => [
                            [
                                'title' => 'Instalații electrice extindere',
                                'status' => TaskStatus::DONE->value,
                                'planned_start' => Carbon::now()->subMonths(4)->addDays(3),
                                'planned_end' => Carbon::now()->subMonths(2)->endOfMonth(),
                                'actual_start' => Carbon::now()->subMonths(4)->addDays(4),
                                'actual_end' => Carbon::now()->subMonths(2)->addDays(10),
                                'progress' => 100,
                            ],
                            [
                                'title' => 'Sisteme sprinklere',
                                'status' => TaskStatus::DONE->value,
                                'planned_start' => Carbon::now()->subMonths(3),
                                'planned_end' => Carbon::now()->subMonth()->endOfMonth(),
                                'actual_start' => Carbon::now()->subMonths(3),
                                'actual_end' => Carbon::now()->subMonth()->subDays(3),
                                'progress' => 100,
                            ],
                        ],
                    ],
                ],
                'resources' => [
                    [
                        'code' => 'RES-LOG-LIFT',
                        'name' => 'Macara mobilă 70t',
                        'resource_type' => 'equipment',
                        'capacity' => 1,
                        'cost_rate' => 2500,
                        'cost_rate_unit' => 'day',
                        'tasks' => [
                            ['title' => 'Montaj prefabricate', 'allocation' => 90],
                            ['title' => 'Acoperiș metalic', 'allocation' => 60],
                        ],
                    ],
                ],
                'site_logs' => [
                    [
                        'date' => Carbon::now()->subMonths(2)->startOfWeek(),
                        'weather' => 'Parțial noros',
                        'temperature' => '20°C',
                        'manpower_count' => 26,
                        'progress_percent' => 85,
                        'summary' => 'Montaj trasee sprinklere și finisaje interioare.',
                        'issues' => 'Coordonare cu furnizorul de sprinklere pentru ultimele racorduri.',
                        'task_progress' => [
                            ['title' => 'Sisteme sprinklere', 'progress' => 80],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($projectsSeed as $projectData) {
            $project = Project::updateOrCreate([
                'code' => $projectData['code'],
            ], [
                'name' => $projectData['name'],
                'description' => $projectData['description'],
                'status' => $projectData['status'],
                'planned_start_date' => optional($projectData['planned_start'])->toDateString(),
                'planned_end_date' => optional($projectData['planned_end'])->toDateString(),
                'actual_start_date' => optional($projectData['actual_start'])->toDateString(),
                'actual_end_date' => optional($projectData['actual_end'])->toDateString(),
            ]);

            if ($project->tasks()->exists()) {
                continue;
            }

            $tasksByTitle = [];

            $createWbs = function (array $nodes, Project $project, ?int $parentId) use (&$createWbs, &$tasksByTitle, $user) {
                foreach ($nodes as $position => $node) {
                    $wbs = WorkBreakdownStructure::updateOrCreate([
                        'project_id' => $project->id,
                        'code' => $node['code'],
                    ], [
                        'parent_id' => $parentId,
                        'name' => $node['name'],
                        'phase_type' => $node['phase_type'] ?? null,
                        'position' => $position + 1,
                        'planned_start_date' => optional($node['planned_start'])->toDateString(),
                        'planned_end_date' => optional($node['planned_end'])->toDateString(),
                    ]);

                    $tasks = $node['tasks'] ?? [];

                    foreach ($tasks as $index => $taskData) {
                        $task = Task::create([
                            'project_id' => $project->id,
                            'wbs_id' => $wbs->id,
                            'title' => $taskData['title'],
                            'description' => $taskData['description'] ?? null,
                            'status' => $taskData['status'] ?? TaskStatus::BACKLOG->value,
                            'assigned_to_id' => $user->id,
                            'planned_start_date' => optional($taskData['planned_start'] ?? null)->toDateString(),
                            'planned_end_date' => optional($taskData['planned_end'] ?? null)->toDateString(),
                            'actual_start_date' => optional($taskData['actual_start'] ?? null)->toDateString(),
                            'actual_end_date' => optional($taskData['actual_end'] ?? null)->toDateString(),
                            'progress_percent' => $taskData['progress'] ?? 0,
                            'position' => $index + 1,
                        ]);

                        if ($task->planned_start_date && $task->planned_end_date) {
                            $task->planned_duration_days = $task->planned_start_date->diffInDays($task->planned_end_date) + 1;
                        }

                        if ($task->actual_start_date && $task->actual_end_date) {
                            $task->actual_duration_days = $task->actual_start_date->diffInDays($task->actual_end_date) + 1;
                        }

                        $task->save();

                        $tasksByTitle[$task->title] = $task;
                    }

                    if (! empty($node['children'])) {
                        $createWbs($node['children'], $project, $wbs->id);
                    }
                }
            };

            $createWbs($projectData['wbs'], $project, null);

            foreach ($projectData['resources'] as $resourceData) {
                $resource = ResourceUnit::updateOrCreate([
                    'project_id' => $project->id,
                    'code' => $resourceData['code'],
                ], [
                    'name' => $resourceData['name'],
                    'resource_type' => $resourceData['resource_type'],
                    'capacity' => $resourceData['capacity'] ?? null,
                    'cost_rate' => $resourceData['cost_rate'] ?? null,
                    'cost_rate_unit' => $resourceData['cost_rate_unit'] ?? 'day',
                    'is_active' => true,
                ]);

                foreach ($resourceData['tasks'] ?? [] as $assignment) {
                    if (! isset($tasksByTitle[$assignment['title']])) {
                        continue;
                    }

                    $resource->tasks()->syncWithoutDetaching([
                        $tasksByTitle[$assignment['title']]->id => [
                            'allocation_percent' => $assignment['allocation'] ?? 100,
                            'planned_hours' => $assignment['planned_hours'] ?? null,
                            'notes' => $assignment['notes'] ?? null,
                        ],
                    ]);
                }
            }

            foreach ($projectData['site_logs'] as $logData) {
                $siteLog = SiteLog::updateOrCreate([
                    'project_id' => $project->id,
                    'log_date' => optional($logData['date'])->toDateString(),
                ], [
                    'author_id' => $user->id,
                    'weather' => $logData['weather'] ?? null,
                    'temperature' => $logData['temperature'] ?? null,
                    'manpower_count' => $logData['manpower_count'] ?? null,
                    'progress_percent' => $logData['progress_percent'] ?? null,
                    'summary' => $logData['summary'] ?? null,
                    'issues' => $logData['issues'] ?? null,
                    'safety_incident_occurred' => $logData['safety_incident_occurred'] ?? false,
                ]);

                foreach ($logData['task_progress'] ?? [] as $progress) {
                    if (! isset($tasksByTitle[$progress['title']])) {
                        continue;
                    }

                    $siteLog->tasks()->syncWithoutDetaching([
                        $tasksByTitle[$progress['title']]->id => [
                            'progress_percent' => $progress['progress'] ?? null,
                            'notes' => $progress['notes'] ?? null,
                        ],
                    ]);
                }
            }
        }
    }
}
