<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    // Dashboard
    public function dashboard()
    {
        $total_users =  User::count();
        $total_task = Task::count();
        $complete_Tasks  =  Task::where('status', 'completed')->count();
        $pending_tasks = Task::where('status', 'pending')->count(); 
        $recent_tasks = Task::with('user')->latest()->take(5)->get();

        return view('admin.dashboard', [
            'total_users' => $total_users,
            'total_task' => $total_task,
            'complete_Tasks' => $complete_Tasks,
            'recent_tasks' => $recent_tasks,
            'pending_tasks' => $pending_tasks
        ]);
    }

    // User Management Methods 
    public function users()
    {
        $users = User::with('role')->latest()->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function createUser()
    {
        $roles = Role::where('id', '!=', Role::ADMIN)->get();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function storeUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Prevent non-admins from creating admin users
        $requestedRole = Role::find($request->role_id);
        if ($requestedRole->name === 'admin' && !Auth::user()->isAdmin()) {
            return redirect()->back()
                ->with('error', 'You are not authorized to create admin users.')
                ->withInput();
        }

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function editUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Please use your profile page to edit your own account.');
        }

        $roles = Role::where('id', '!=', Role::ADMIN)->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function updateUser(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Please use your profile page to edit your own account.');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Prevent non-admins from assigning admin role
        $requestedRole = Role::find($request->role_id);
        if ($requestedRole->name === 'admin' && !Auth::user()->isAdmin()) {
            return redirect()->back()
                ->with('error', 'You are not authorized to assign admin role.')
                ->withInput();
        }

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'role_id' => $request->role_id,
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /* Delete USer */
    public function deleteUser(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting the primary admin account (optional safety measure)
        if ($user->id === 1) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot delete the primary administrator account.');
        }

        try {
            /** Check Handle tasks  */
            if ($user->tasks()->count() > 0) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Cannot delete user because they have tasks assigned. Please reassign or delete their tasks first.');
            }
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }

    // Additional admin methods for task management 
    public function allTasks()
    {
        $tasks = Task::with(['user', 'assignedUser'])->latest()->paginate(10);
        return view('admin.tasks.index', compact('tasks'));
    }


    public function showTask(Task $task)
    {
        return view('admin.tasks.show', compact('task'));
    }


    /** Delete Task with Document */
    public function deleteTask(Task $task)
    {
        try {
            foreach ($task->documents as $document) {
                Storage::delete($document->file_path);
                $document->delete();
            }

            $task->delete();

            return redirect()->route('admin.tasks.index')
                ->with('success', 'Task deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.tasks.index')
                ->with('error', 'Error deleting task: ' . $e->getMessage());
        }
    }

    // Manager methods

    public function managerDashboard()
    {
        $user = Auth::user();
        $team_tasks = Task::where('assigned_to', $user->id)
            ->orWhereHas('assignedUser', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            })
            ->with(['user', 'assignedUser'])
            ->latest()
            ->paginate(10);

        return view('manager.dashboard', compact('team_tasks'));
    }

    public function teamTasks()
    {
        $user = Auth::user();
        $tasks = Task::whereHas('assignedUser', function ($q) use ($user) {
            $q->where('manager_id', $user->id);
        })
            ->with(['user', 'assignedUser'])
            ->latest()
            ->paginate(10);

        return view('manager.tasks.index', compact('tasks'));
    }

    public function showTeamTask(Task $task)
    {
        return view('manager.tasks.show', compact('task'));
    }

    public function assignTask(Request $request, Task $task)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $task->update(['assigned_to' => $request->assigned_to]);

            return redirect()->back()
                ->with('success', 'Task assigned successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error assigning task: ' . $e->getMessage());
        }
    }

    public function teamMembers()
    {
        $user = Auth::user();
        $team_members = User::where('manager_id', $user->id)->get();
        return view('manager.team.index', compact('team_members'));
    }

    public function teamPerformance()
    {
        $user = Auth::user();
        $team_members = User::where('manager_id', $user->id)->withCount(['tasks', 'assignedTasks'])->get();

        $performance_data = [];
        foreach ($team_members as $member) {
            $performance_data[] = [
                'name' => $member->name,
                'tasks_created' => $member->tasks_count,
                'tasks_assigned' => $member->assigned_tasks_count,
                'tasks_completed' => $member->tasks()->where('status', 'completed')->count(),
            ];
        }

        return view('manager.team.performance', compact('performance_data'));
    }
}
