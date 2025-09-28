<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
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

        Task::factory()
            ->count(3)
            ->backlog()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create();

        Task::factory()
            ->count(3)
            ->inProgress()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create([
                'assigned_to_id' => $user->id,
                'due_date' => now()->addWeek(),
            ]);

        Task::factory()
            ->count(2)
            ->inReview()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create([
                'assigned_to_id' => $user->id,
                'due_date' => now()->addWeeks(2),
            ]);

        Task::factory()
            ->count(2)
            ->done()
            ->sequence(fn ($sequence) => ['position' => $sequence->index + 1])
            ->create([
                'assigned_to_id' => $user->id,
            ]);
    }
}
