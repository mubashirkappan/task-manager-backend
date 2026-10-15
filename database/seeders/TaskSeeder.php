<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Run UserSeeder first.');
            return;
        }

        $statuses = ['pending', 'in_progress', 'completed'];

        // Curated set of tasks per user
        foreach ($users as $user) {
            // Give each user between 3 and 8 tasks
            $taskCount = rand(3, 8);

            for ($i = 1; $i <= $taskCount; $i++) {
                Task::create([
                    'user_id'     => $user->id,
                    'title'       => ucfirst(fake()->words(rand(3, 6), true)),
                    'description' => fake()->paragraph(rand(1, 3)),
                    'status'      => fake()->randomElement($statuses),
                    'created_at'  => fake()->dateTimeBetween('-1 month', 'now'),
                ]);
            }
        }

        // Add specific known tasks to User 1 for quick manual testing
        $user1 = User::where('email', 'user1@example.com')->first();
        if ($user1) {
            Task::create([
                'user_id'     => $user1->id,
                'title'       => 'Urgent: Review Security Audit',
                'description' => 'Check token expiration policy and API throttling.',
                'status'      => 'pending',
                'created_at'  => now(),
            ]);

            Task::create([
                'user_id'     => $user1->id,
                'title'       => 'Deploy Frontend App to Vercel',
                'description' => 'Configure environment variables and run build pipeline.',
                'status'      => 'completed',
                'created_at'  => now()->subDays(2),
            ]);
        }
    }
}
