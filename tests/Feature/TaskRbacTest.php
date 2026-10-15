<?php

use App\Models\Task;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user1 = User::factory()->create();
    $this->user2 = User::factory()->create();

    $this->task1 = Task::factory()->create([
        'user_id' => $this->user1->id,
        'title'   => 'User 1 Specific Task',
        'status'  => 'pending',
    ]);

    $this->task2 = Task::factory()->create([
        'user_id' => $this->user2->id,
        'title'   => 'User 2 Specific Task',
        'status'  => 'in_progress',
    ]);
});

/*
|--------------------------------------------------------------------------
| Unauthenticated Guard Tests
|--------------------------------------------------------------------------
*/
describe('Unauthenticated Access', function () {
    it('blocks unauthenticated requests to all task endpoints', function () {
        $this->getJson('/api/tasks')->assertUnauthorized();
        $this->postJson('/api/tasks', ['title' => 'Unauthorized Task'])->assertUnauthorized();
        $this->getJson("/api/tasks/{$this->task1->id}")->assertUnauthorized();
        $this->putJson("/api/tasks/{$this->task1->id}", ['title' => 'Updated Title'])->assertUnauthorized();
        $this->deleteJson("/api/tasks/{$this->task1->id}")->assertUnauthorized();
    });
});

/*
|--------------------------------------------------------------------------
| Regular User Isolation & Permissions (User cannot touch other users' tasks)
|--------------------------------------------------------------------------
*/
describe('Regular User RBAC & Tenant Isolation', function () {
    it('scopes index query to only return the authenticated user\'s tasks', function () {
        $response = $this->actingAs($this->user1, 'api')
            ->getJson('/api/tasks')
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->task1->id)
            ->assertJsonMissing(['id' => $this->task2->id]);
    });

    it('allows a user to view their own task', function () {
        $this->actingAs($this->user1, 'api')
            ->getJson("/api/tasks/{$this->task1->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->task1->id)
            ->assertJsonPath('data.user.id', $this->user1->id);
    });

    it('returns 403 Forbidden when a user attempts to view another user\'s task', function () {
        $this->actingAs($this->user1, 'api')
            ->getJson("/api/tasks/{$this->task2->id}")
            ->assertForbidden();
    });

    it('creates a task assigned strictly to the authenticated user', function () {
        $payload = [
            'title'       => 'User 1 New Task',
            'description' => 'Self created task description',
            'status'      => 'pending',
        ];

        $response = $this->actingAs($this->user1, 'api')
            ->postJson('/api/tasks', $payload)
            ->assertCreated();

        $response->assertJsonPath('data.title', 'User 1 New Task')
            ->assertJsonPath('data.user.id', $this->user1->id);

        $this->assertDatabaseHas('tasks', [
            'title'   => 'User 1 New Task',
            'user_id' => $this->user1->id,
        ]);
    });

    it('allows a user to update their own task', function () {
        $this->actingAs($this->user1, 'api')
            ->putJson("/api/tasks/{$this->task1->id}", [
                'title'  => 'Updated By Owner',
                'status' => 'completed',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated By Owner')
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('tasks', [
            'id'    => $this->task1->id,
            'title' => 'Updated By Owner',
        ]);
    });

    it('returns 403 Forbidden when a user attempts to update another user\'s task', function () {
        $this->actingAs($this->user1, 'api')
            ->putJson("/api/tasks/{$this->task2->id}", [
                'title' => 'Tampered Title',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', [
            'id'    => $this->task2->id,
            'title' => 'Tampered Title',
        ]);
    });

    it('allows a user to delete their own task', function () {
        $this->actingAs($this->user1, 'api')
            ->deleteJson("/api/tasks/{$this->task1->id}")
            ->assertOk()
            ->assertJson(['message' => 'Task deleted successfully']);

        $this->assertDatabaseMissing('tasks', [
            'id' => $this->task1->id,
        ]);
    });

    it('returns 403 Forbidden when a user attempts to delete another user\'s task', function () {
        $this->actingAs($this->user1, 'api')
            ->deleteJson("/api/tasks/{$this->task2->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task2->id,
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Privileges (Admin can access and modify any task)
|--------------------------------------------------------------------------
*/
describe('Admin Privileges & Omnipresence', function () {
    it('allows an admin to see all tasks from all users in the index listing', function () {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/tasks')
            ->assertOk();

        // 2 tasks in total from user1 and user2
        $response->assertJsonCount(2, 'data');
    });

    it('allows an admin to view any user\'s task', function () {
        $this->actingAs($this->admin, 'api')
            ->getJson("/api/tasks/{$this->task1->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->task1->id);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/tasks/{$this->task2->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $this->task2->id);
    });

    it('allows an admin to update any user\'s task', function () {
        $this->actingAs($this->admin, 'api')
            ->putJson("/api/tasks/{$this->task1->id}", [
                'title' => 'Admin Edited This Title',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Admin Edited This Title');

        $this->assertDatabaseHas('tasks', [
            'id'    => $this->task1->id,
            'title' => 'Admin Edited This Title',
        ]);
    });

    it('allows an admin to delete any user\'s task', function () {
        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/tasks/{$this->task1->id}")
            ->assertOk()
            ->assertJson(['message' => 'Task deleted successfully']);

        $this->assertDatabaseMissing('tasks', [
            'id' => $this->task1->id,
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Search, Status Filter & Pagination Tests
|--------------------------------------------------------------------------
*/
describe('Search, Status Filter, and Pagination Controls', function () {
    it('filters tasks by status while respecting user scope', function () {
        Task::factory()->create([
            'user_id' => $this->user1->id,
            'status'  => 'completed',
            'title'   => 'Deploy Service',
        ]);

        $response = $this->actingAs($this->user1, 'api')
            ->getJson('/api/tasks?status=completed')
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.title', 'Deploy Service');
    });

    it('searches tasks by title keywords', function () {
        Task::factory()->create([
            'user_id' => $this->user1->id,
            'title'   => 'Run Database Optimization',
        ]);

        $response = $this->actingAs($this->user1, 'api')
            ->getJson('/api/tasks?search=Optimization')
            ->assertOk();

        $response->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Run Database Optimization');
    });

    it('supports custom per_page pagination limits', function () {
        Task::factory()->count(10)->create(['user_id' => $this->user1->id]);

        $response = $this->actingAs($this->user1, 'api')
            ->getJson('/api/tasks?per_page=5')
            ->assertOk();

        $response->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    });
});
