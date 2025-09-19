<?php

namespace App\Http\Controllers;

use App\Models\Document;
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
        $user = Auth::user();

        if ($user->isAdmin() || $user->isManager()) {
            $tasks = Task::with(['user', 'assignedUser', 'documents'])
                ->latest()
                ->paginate(10);
        } else {
            $tasks = Task::where('user_id', $user->id)
                ->orWhere('assigned_to', $user->id)
                ->with(['user', 'assignedUser', 'documents'])
                ->latest()
                ->paginate(10);
        }
        // echo $user->id;exit;
        // echo "<pre>";
        // print_R($tasks->toArray());
        // exit; 
        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        $users = User::where('id', '!=', Auth::id())->get();
        return view('tasks.create', compact('users'));
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

        return view('tasks.show', compact('task'));
    }

    public function edit($id)
    {
        $task = Task::findOrFail($id);
        $users = User::where('id', '!=', Auth::id())->get();

        $user = Auth::user();
        if (!$user->isAdmin() && $task->user_id !== $user->id) {
            return redirect()->route('tasks.index')
                ->with('error', 'Unauthorized access.');
        }

        // echo "<pre>";
        // print_r($users);
        // exit;

        return view('tasks.edit', compact('task', 'users'));
    }

    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        // Authorization check
        $user = Auth::user();
        if (!$user->isAdmin() && $task->user_id !== $user->id) {
            return redirect()->route('tasks.index')
                ->with('error', 'Unauthorized access.');
        }

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

    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        // Authorization check
        $user = Auth::user();
        if (!$user->isAdmin() && $task->user_id !== $user->id) {
            return redirect()->route('tasks.index')
                ->with('error', 'Unauthorized access.');
        }

        // Delete associated documents
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
