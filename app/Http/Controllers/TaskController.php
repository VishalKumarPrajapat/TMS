<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            /** Admin  Data */
            $directUsers = User::where('created_by', $user->id)->pluck('id');
            $managerIds = User::where('role_id', Role::MANAGER)
                ->where('created_by', $user->id)
                ->pluck('id');
            $indirectUsers = User::whereIn('created_by', $managerIds)->pluck('id');

            $userIds = $directUsers->merge($indirectUsers)->unique();

            $tasks = Task::with(['user', 'assignedUser', 'documents'])
                ->where(function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds)
                        ->orWhereIn('assigned_to', $userIds);
                })
                ->latest()
                ->paginate(10);
        } elseif ($user->isManager()) {
            /** Manager Data */
            $userIds = User::where('created_by', $user->id)->pluck('id');

            $tasks = Task::with(['user', 'assignedUser', 'documents'])
                ->where(function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds)
                        ->orWhereIn('assigned_to', $userIds);
                })
                ->latest()
                ->paginate(10);
        } else {
            // User Data
            $tasks = Task::where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('assigned_to', $user->id);
            })
                ->with(['user', 'assignedUser', 'documents'])
                ->latest()
                ->paginate(10);
        }
        return view('tasks.index', ['tasks' => $tasks]);
    }


    /** Create TAsk */
    public function create()
    {
        $currentUser = auth()->user();
        if ($currentUser->isAdmin()) {
            $users = User::where(function ($query) use ($currentUser) {
                $query->where('created_by', $currentUser->id)
                    ->orWhereHas('createdBy', function ($query) use ($currentUser) {
                        $query->where('created_by', $currentUser->id); // Filter users created by managers
                    });
            })->get();
        } else {
            $users = User::where('created_by', $currentUser->id)->get();
        }

        return view(
            'tasks.create',
            [
                'currentUser' => $currentUser,
                'users' => $users
            ]
        );
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'deadline' => 'required|date|after:now',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'deadline' => $request->deadline,
            'user_id' => Auth::id(),
            'assigned_to' => $request->assigned_to,
            'status' => Task::STATUS_PENDING
        ]);

        return redirect()->route('tasks.show', $task->id)
            ->with('success', 'Task created successfully.');
    }

    public function show($id)
    {
        $task = Task::with(['user', 'assignedUser', 'documents'])->findOrFail($id);

        // Authorization check
        $user = Auth::user();
        if (
            !$user->isAdmin() && !$user->isManager() &&
            $task->user_id !== $user->id && $task->assigned_to !== $user->id
        ) {
            return redirect()->route('tasks.index')
                ->with('error', 'Unauthorized access.');
        }

        return view('tasks.show', ['task' => $task]);
    }


    /*** Edit Task */
    public function edit($id)
    {
        $currentUser = auth()->user();
        $task = Task::findOrFail($id);

        if ($currentUser->isAdmin()) {
            $users = User::where(function ($query) use ($currentUser) {
                $query->where('created_by', $currentUser->id)
                    ->orWhereHas('createdBy', function ($query) use ($currentUser) {
                        $query->where('created_by', $currentUser->id);
                    });
            })->get();
        } else {
            $users = User::where('created_by', $currentUser->id)->get();
        }
        // echo "<pre>";
        // print_r($users);
        // exit;
        return view('tasks.edit', ['task' => $task, 'users' => $users]);
    }


    /** Updated TAsk */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high',
            'deadline' => 'required|date',
            'status' => 'required|in:pending,in_progress,completed',
            'assigned_to' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $task->update($request->all());

        return redirect()->route('tasks.show', $task->id)
            ->with('success', 'Task updated successfully.');
    }


    /** Dellete Task */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        $user = Auth::user();
        if (!$user->isAdmin() && $task->user_id !== $user->id) {
            return redirect()->route('tasks.index')
                ->with('error', 'Unauthorized access.');
        }

        foreach ($task->documents as $document) {
            Storage::delete($document->file_path);
            $document->delete();
        }
        $task->delete();

        return redirect()->route('tasks.index')
            ->with('success', 'Task deleted successfully.');
    }

    public function uploadDocument(Request $request, $taskId)
    {
        $task = Task::findOrFail($taskId);

        // Authorization check
        $user = Auth::user();
        if (!$user->isAdmin() && $task->user_id !== $user->id) {
            return redirect()->back()
                ->with('error', 'Unauthorized access.');
        }

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png,txt'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->store('documents');

            $task->documents()->create([
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('success', 'Document uploaded successfully.');
        }

        return redirect()->back()
            ->with('error', 'Failed to upload document.');
    }

    public function downloadDocument($documentId)
    {
        $document = Document::findOrFail($documentId);
        $task = $document->task;

        // Authorization check
        $user = Auth::user();
        if (
            !$user->isAdmin() && !$user->isManager() &&
            $task->user_id !== $user->id && $task->assigned_to !== $user->id
        ) {
            return redirect()->back()
                ->with('error', 'Unauthorized access.');
        }

        if (!Storage::exists($document->file_path)) {
            return redirect()->back()
                ->with('error', 'File not found.');
        }
        return Storage::download($document->file_path, $document->original_name);
    }

    public function deleteDocument($documentId)
    {
        $document = Document::findOrFail($documentId);
        $task = $document->task;
        // Authorization check
        $user = Auth::user();
        if (!$user->isAdmin() && $task->user_id !== $user->id) {
            return redirect()->back()
                ->with('error', 'Unauthorized access.');
        }

        Storage::delete($document->file_path);
        $document->delete();

        return redirect()->back()
            ->with('success', 'Document deleted successfully.');
    }
}
