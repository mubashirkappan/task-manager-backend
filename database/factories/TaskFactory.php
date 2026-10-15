<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status'      => fake()->randomElement(['pending', 'in_progress', 'completed']),
        ];
    }
}
