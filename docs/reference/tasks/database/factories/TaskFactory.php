<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state: an open task without a due date.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'due_on' => null,
            'status' => TaskStatus::Open,
            'completed_at' => null,
        ];
    }

    /**
     * A task that has been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TaskStatus::Done,
            'completed_at' => now(),
        ]);
    }

    /**
     * A task whose due date was yesterday.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_on' => today()->subDay(),
        ]);
    }
}
