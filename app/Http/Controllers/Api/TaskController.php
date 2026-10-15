<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Task::class);

        $user = $request->user();
        $perPage = min((int) $request->query('per_page', 10), 100);

        $query = Task::with('user')
            ->search($request->query('search'))
            ->filterStatus($request->query('status'))
            ->latest();

        // Enforce role isolation: Regular users only see their own tasks
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $tasks = $query->paginate($perPage);

        return TaskResource::collection($tasks);
    }
    public function store(StoreTaskRequest $request): JsonResponse
    {
        Gate::authorize('create', Task::class);

        $task = $request->user()->tasks()->create($request->validated());
        $task->load('user');

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }
    public function show(Task $task): TaskResource
    {
        Gate::authorize('view', $task);

        $task->load('user');

        return new TaskResource($task);
    }
    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        Gate::authorize('update', $task);

        $task->update($request->validated());
        $task->load('user');

        return new TaskResource($task);
    }
    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully'
        ], 200);
    }
}
