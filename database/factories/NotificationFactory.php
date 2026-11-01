<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\PasswordChanged;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * Define the model's default state: an unread notification for a new user.
     * Give it an owner with ->for($user, 'notifiable').
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'type' => PasswordChanged::class,
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => User::factory(),
            'data' => ['message' => 'Your password was changed.'],
            'read_at' => null,
        ];
    }

    /**
     * A notification the user has already read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
