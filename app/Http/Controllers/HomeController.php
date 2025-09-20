<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $userName = $user->name;

        // Initialize variables
        $createTasks = 0;
        $assignedTasks = 0;
        $completeTask = 0;
        $totalTask = 0;
        $pendingTask = 0;
        $inProgressTask = 0;

        if ($user->isAdmin()) {
            $directUsers = User::where('created_by', $user->id)->pluck('id');
            $managerIds = User::where('role_id', Role::MANAGER)
                ->where('created_by', $user->id)
                ->pluck('id');
            $indirectUsers = User::whereIn('created_by', $managerIds)->pluck('id');

            $userIds = $directUsers->merge($indirectUsers)->unique();
        } elseif ($user->isManager()) {
            $userIds = User::where('created_by', $user->id)->pluck('id');
        } else {
            $userIds = collect([$user->id]); // just the current user
        }

        // Task queries for all roles (filtered by user_id or assigned_to)
        $taskQuery = Task::where(function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds)
                ->orWhereIn('assigned_to', $userIds);
        });

        $totalTask = (clone $taskQuery)->count();
        $createTasks = Task::whereIn('user_id', $userIds)->count();
        $assignedTasks = Task::whereIn('assigned_to', $userIds)->count();
        $completeTask = (clone $taskQuery)->where('status', Task::STATUS_COMPLETED)->count();
        $pendingTask = (clone $taskQuery)->where('status', Task::STATUS_PENDING)->count();
        $inProgressTask = (clone $taskQuery)->where('status', Task::STATUS_IN_PROGRESS)->count();

        return view('home', [
            'userName' => $userName,
            'createTasks' => $createTasks,
            'assignedTasks' => $assignedTasks,
            'completeTask' => $completeTask,
            'totalTask' => $totalTask,
            'pendingTask' => $pendingTask,
            'inProgressTask' => $inProgressTask,
            'isAdmin' => $user->isAdmin(),
            'isMAnager' => $user->isManager(),
        ]);
    }
}
