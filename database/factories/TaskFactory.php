<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status' => collect(TaskStatus::cases())
                ->map(fn (TaskStatus $status) => $status->value)
                ->random(),
            'assigned_to_id' => null,
            'due_date' => $this->faker->optional()->dateTimeBetween('now', '+2 months'),
            'position' => $this->faker->numberBetween(1, 50),
        ];
    }

    public function backlog(): self
    {
        return $this->state(fn () => ['status' => TaskStatus::BACKLOG->value]);
    }

    public function inProgress(): self
    {
        return $this->state(fn () => ['status' => TaskStatus::IN_PROGRESS->value]);
    }

    public function done(): self
    {
        return $this->state(fn () => ['status' => TaskStatus::DONE->value]);
    }

    public function inReview(): self
    {
        return $this->state(fn () => ['status' => TaskStatus::IN_REVIEW->value]);
    }
}
