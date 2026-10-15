<?php

use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();

    // Create tasks for user
    Task::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'status' => 'pending',
    ]);
    Task::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'completed',
    ]);
});

describe('User Dashboard Endpoint', function () {
    it('returns only personal stats for regular user', function () {
        // Create an unrelated task from admin
        Task::factory()->create([
            'user_id' => $this->admin->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/dashboard/user')
            ->assertOk();

        $response->assertJsonCount(3, 'recent_tasks');
        expect(collect($response->json('recent_tasks'))->pluck('user.id')->unique()->all())
            ->toBe([$this->user->id]);

        $response->assertJson([
            'total_tasks' => 3,
            'status_breakdown' => [
                'pending' => 2,
                'completed' => 1,
            ],
        ]);
    });
});

describe('Admin Dashboard Endpoint & Middleware', function () {
    it('blocks regular users with 403 Forbidden', function () {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/dashboard/admin')
            ->assertForbidden()
            ->assertJson([
                'message' => 'Unauthorized. Administrator access required.',
            ]);
    });

    it('allows admins to access system-wide analytics', function () {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/dashboard/admin')
            ->assertOk();

        $response->assertJsonStructure([
            'users_summary' => ['total', 'regular_users', 'admins'],
            'tasks_summary' => ['total', 'pending', 'in_progress', 'completed'],
            'users' => [['id', 'name', 'email', 'role', 'tasks_count', 'by_status' => ['pending', 'in_progress', 'completed']]],
            'recent_activity',
        ]);
    });

    it('returns per-user task counts by status, busiest users first', function () {
        Task::factory()->create(['user_id' => $this->admin->id, 'status' => 'in_progress']);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/dashboard/admin')
            ->assertOk()
            ->assertJsonPath('users_summary', ['total' => 2, 'regular_users' => 1, 'admins' => 1])
            ->assertJsonPath('tasks_summary.total', 4)
            ->assertJsonPath('users.0.id', $this->user->id)
            ->assertJsonPath('users.0.tasks_count', 3)
            ->assertJsonPath('users.0.by_status', ['pending' => 2, 'in_progress' => 0, 'completed' => 1])
            ->assertJsonPath('users.1.id', $this->admin->id)
            ->assertJsonPath('users.1.by_status.in_progress', 1);
    });

    it('blocks guests from both dashboards', function () {
        $this->getJson('/api/dashboard/user')->assertUnauthorized();
        $this->getJson('/api/dashboard/admin')->assertUnauthorized();
    });
});
