<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ResourceUnit;
use App\Models\SiteLog;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkBreakdownStructure;
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

        $project = Project::firstOrCreate(
            ['code' => 'PRJ-001'],
            [
                'name' => 'Clădire birouri – București',
                'description' => 'Proiect demonstrativ pentru modul de planificare.',
                'planned_start_date' => now()->startOfMonth(),
                'planned_end_date' => now()->addMonths(6),
                'status' => 'planning',
            ]
        );

        $structure = WorkBreakdownStructure::firstOrCreate([
            'project_id' => $project->id,
            'name' => 'Structură',
            'code' => 'WBS-STR',
        ], [
            'position' => 1,
            'phase_type' => 'structura',
            'planned_start_date' => now()->startOfMonth(),
            'planned_end_date' => now()->addMonths(2),
        ]);

        $installations = WorkBreakdownStructure::firstOrCreate([
            'project_id' => $project->id,
            'name' => 'Instalații',
            'code' => 'WBS-INS',
        ], [
            'position' => 2,
            'phase_type' => 'instalatii',
            'planned_start_date' => now()->addMonths(2),
            'planned_end_date' => now()->addMonths(5),
        ]);

        $finishes = WorkBreakdownStructure::firstOrCreate([
            'project_id' => $project->id,
            'name' => 'Finisaje',
            'code' => 'WBS-FIN',
        ], [
            'position' => 3,
            'phase_type' => 'finisaje',
            'planned_start_date' => now()->addMonths(4),
            'planned_end_date' => now()->addMonths(6),
        ]);

        Task::factory()
            ->count(3)
            ->backlog()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create([
                'project_id' => $project->id,
                'wbs_id' => $structure->id,
                'assigned_to_id' => $user->id,
                'planned_start_date' => now()->startOfMonth()->addDays(5),
                'planned_end_date' => now()->startOfMonth()->addDays(25),
                'progress_percent' => 10,
            ]);

        Task::factory()
            ->count(3)
            ->inProgress()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create([
                'project_id' => $project->id,
                'wbs_id' => $installations->id,
                'assigned_to_id' => $user->id,
                'planned_start_date' => now()->addMonths(2),
                'planned_end_date' => now()->addMonths(3),
                'progress_percent' => 35,
            ]);

        Task::factory()
            ->count(2)
            ->inReview()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create([
                'project_id' => $project->id,
                'wbs_id' => $finishes->id,
                'assigned_to_id' => $user->id,
                'planned_start_date' => now()->addMonths(4),
                'planned_end_date' => now()->addMonths(5),
                'progress_percent' => 60,
            ]);

        Task::factory()
            ->count(2)
            ->done()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create([
                'project_id' => $project->id,
                'wbs_id' => $structure->id,
                'assigned_to_id' => $user->id,
                'progress_percent' => 100,
                'actual_start_date' => now()->subMonths(1),
                'actual_end_date' => now()->subDays(10),
            ]);

        ResourceUnit::firstOrCreate([
            'project_id' => $project->id,
            'name' => 'Echipa Cofraj',
        ], [
            'resource_type' => 'crew',
            'capacity' => 8,
            'cost_rate' => 1500,
            'cost_rate_unit' => 'day',
        ]);

        ResourceUnit::firstOrCreate([
            'project_id' => $project->id,
            'name' => 'Macara turn',
        ], [
            'resource_type' => 'equipment',
            'capacity' => 1,
            'cost_rate' => 2000,
            'cost_rate_unit' => 'day',
        ]);

        SiteLog::firstOrCreate([
            'project_id' => $project->id,
            'log_date' => now()->toDateString(),
        ], [
            'author_id' => $user->id,
            'weather' => 'Însorit',
            'temperature' => '25°C',
            'manpower_count' => 35,
            'progress_percent' => 32.5,
            'summary' => 'Turnare placă nivel 3 și montaj rețele instalații verticale.',
            'issues' => 'Întârziere livrare armătură pentru zona B.',
            'safety_incident_occurred' => false,
        ]);
    }
}
