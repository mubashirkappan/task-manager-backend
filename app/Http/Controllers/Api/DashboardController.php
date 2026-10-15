<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{

    private const STATUSES = ['pending', 'in_progress', 'completed'];

    /**
     * Personal dashboard metrics for the authenticated user (their own tasks only).
     */
    public function userStats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $taskCounts = Task::where('user_id', $userId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $totalTasks = Task::where('user_id', $userId)->count();

        $recentTasks = Task::with('user')
            ->where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'total_tasks' => $totalTasks,
            'status_breakdown' => [
                'pending'     => (int) ($taskCounts['pending'] ?? 0),
                'in_progress' => (int) ($taskCounts['in_progress'] ?? 0),
                'completed'   => (int) ($taskCounts['completed'] ?? 0),
            ],
            'recent_tasks' => TaskResource::collection($recentTasks),
        ]);
    }

    /**
     * Return admin dashboard metrics (system-wide aggregates).
     */
    public function adminStats(): JsonResponse
    {
        $totalUsers = User::count();
        $totalAdmins = User::where('role', UserRole::ADMIN)->count();
        $totalRegularUsers = User::where('role', UserRole::USER)->count();

        $totalTasks = Task::count();

        $taskStatusCounts = Task::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentTasks = Task::with('user')
            ->latest()
            ->take(5)
            ->get();

        // Per-user task counts (total + one count per status), busiest users first.
        $users = User::query()
            ->withCount(array_merge(['tasks'], ...array_map(
                fn (string $status) => ["tasks as {$status}_count" => fn ($q) => $q->where('status', $status)],
                self::STATUSES,
            )))
            ->orderByDesc('tasks_count')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role->value,
                'tasks_count' => $user->tasks_count,
                'by_status'   => collect(self::STATUSES)->mapWithKeys(fn ($s) => [$s => $user->{"{$s}_count"}]),
            ]);

        return response()->json([
            'users_summary' => [
                'total'         => $totalUsers,
                'regular_users' => $totalRegularUsers,
                'admins'        => $totalAdmins,
            ],
            'tasks_summary' => [
                'total'       => $totalTasks,
                'pending'     => (int) ($taskStatusCounts['pending'] ?? 0),
                'in_progress' => (int) ($taskStatusCounts['in_progress'] ?? 0),
                'completed'   => (int) ($taskStatusCounts['completed'] ?? 0),
            ],
            'users'           => $users,
            'recent_activity' => TaskResource::collection($recentTasks),
        ]);
    }
}
