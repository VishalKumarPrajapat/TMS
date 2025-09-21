<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ManagerController extends Controller
{
    public function dashboard()
    {
        $manager = auth()->user();
        $userIds = User::where('created_by', $manager->id)->pluck('id');
        $total_users = $userIds->count();
        $taskQuery = Task::where(function ($query) use ($userIds) {
            $query->whereIn('user_id', $userIds)
                ->orWhereIn('assigned_to', $userIds);
        });
        $total_task = (clone $taskQuery)->count();
        $complete_Tasks = (clone $taskQuery)->where('status', Task::STATUS_COMPLETED)->count();
        $pending_tasks = (clone $taskQuery)->where('status', Task::STATUS_PENDING)->count();
        $recent_tasks = (clone $taskQuery)->with('user')->latest()->take(5)->get();

        return view('manager.dashboard', [
            'total_users' => $total_users,
            'total_task' => $total_task,
            'complete_Tasks' => $complete_Tasks,
            'pending_tasks' => $pending_tasks,
            'recent_tasks' => $recent_tasks
        ]);
    }


    /** Manager Create Users List */
    public function users()
    {
        $currentUser = auth()->user();
        $users = User::where('created_by', $currentUser->id)->with('role')
            ->latest()
            ->paginate(10);
        return view('manager.users.index', ['users' => $users]);
    }


    /** Create New User thought of manager */
    public function createUser()
    {
        $roles = Role::where('id', '!=', Role::ADMIN)->where('id', '!=', Role::MANAGER)->get();
        return view('manager.users.create', ['roles' => $roles]);
    }


    /*  Store User   */
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

            return redirect()->route('manager.users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating user: ' . $e->getMessage())
                ->withInput();
        }
    }


    /* Edit USer*/
    public function editUser(User $user)
    {
        $roles = Role::where('id', '!=', Role::ADMIN)->where('id', '!=', Role::MANAGER)->get();
        return view('manager.users.edit', ['user' => $user, 'roles' => $roles]);
    }


    /** Update MAngaer Created User */
    public function updateUser(Request $request, User $user)
    {
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
        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'role_id' => $request->role_id,
            ]);

            return redirect()->route('manager.users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating user: ' . $e->getMessage())
                ->withInput();
        }
    }


    /* Delete USer without You  */
    public function deleteUser(User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->route('manager.users.index')
                ->with('error', 'You cannot delete your own account.');
        }
        try {
            /** Check tasks if Exist not deletE */
            if ($user->tasks()->count() > 0) {
                return redirect()->route('manager.users.index')
                    ->with('error', 'Cannot delete user because they have tasks assigned. Please reassign or delete their tasks first.');
            }
            $user->delete();

            return redirect()->route('manager.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('manager.users.index')
                ->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }
}
