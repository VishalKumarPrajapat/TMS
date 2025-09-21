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
    protected $currentUser;

    public function __construct()
    {
        $this->middleware('admin');
        $this->currentUser = auth()->user();
    }

    // Dashboard  
    public function dashboard()
    {
        $admin = auth()->user();
        /**Get & Count All managers and users */
        $directUsers = User::where('created_by', $admin->id)->pluck('id');
        $managerIds = User::where('role_id', Role::MANAGER)->where('created_by', $admin->id)->pluck('id');
        $indirectUsers = User::whereIn('created_by', $managerIds)->pluck('id');

        $userIds  = $directUsers->merge($indirectUsers)->unique();
        $total_users = User::whereIn('id', $userIds)->count();

        $total_task = Task::where(function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds)
                ->orWhereIn('assigned_to', $userIds);
        })->count();

        $complete_Tasks = Task::where(function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds)
                ->orWhereIn('assigned_to', $userIds);
        })->where('status', 'completed')->count();

        $pending_tasks = Task::where(function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds)
                ->orWhereIn('assigned_to', $userIds);
        })->where('status', 'pending')->count();

        $recent_tasks = Task::with('user')->where(function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds)
                ->orWhereIn('assigned_to', $userIds);
        })->latest()->take(5)->get();

        return view('admin.dashboard', [
            'total_users' => $total_users,
            'total_task' => $total_task,
            'complete_Tasks' => $complete_Tasks,
            'pending_tasks' => $pending_tasks,
            'recent_tasks' => $recent_tasks
        ]);
    }

    /** User Management Methods  */
    public function users()
    {
        $currentUser = auth()->user();
        $users = User::where(function ($query) use ($currentUser) {
            $query->where('created_by', $currentUser->id)
                ->orWhereHas('createdBy', function ($query) use ($currentUser) {
                    $query->where('created_by', $currentUser->id);
                });
        })
            ->with('role')
            ->latest()
            ->paginate(10);
        return view('admin.users.index', ['users' => $users]);
    }

    /* Create Admin User*/
    public function createUser()
    {
        $roles = Role::where('id', '!=', Role::ADMIN)->get();
        return view('admin.users.create', compact('roles'));
    }

    /* Storee Useer*/
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

        try {
            $admin = auth()->user();

            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $request->role_id,
                'created_by' => $admin->id,
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating user: ' . $e->getMessage())
                ->withInput();
        }
    }

    /*Edit User Admins */
    public function editUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Please use your profile page to edit your own account.');
        }

        $roles = Role::where('id', '!=', Role::ADMIN)->get();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /*Update*/
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
        if ($user->id === Auth::id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
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
}
