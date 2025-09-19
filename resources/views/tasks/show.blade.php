@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Task Details</h4>
                <div>
                    <a href="{{ route('tasks.edit', $task->id) }}" class="btn btn-warning btn-sm">Edit</a>
                    <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this task?')">Delete</button>
                    </form>
                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary btn-sm">Back to List</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-8">
                        <h3>{{ $task->title }}</h3>
                        <p class="text-muted">Created by: {{ $task->user->name }} on {{ $task->created_at->format('M d, Y H:i') }}</p>
                        
                        <div class="mb-3">
                            <h5>Description</h5>
                            <p>{{ $task->description }}</p>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Task Information</h5>
                                
                                <div class="mb-2">
                                    <strong>Priority:</strong>
                                    <span class="badge bg-{{ $task->priority === 'high' ? 'danger' : ($task->priority === 'medium' ? 'warning' : 'info') }} float-end">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                </div>
                                
                                <div class="mb-2">
                                    <strong>Status:</strong>
                                    <span class="badge bg-{{ $task->status === 'completed' ? 'success' : ($task->status === 'in_progress' ? 'primary' : 'secondary') }} float-end">
                                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                    </span>
                                </div>
                                
                                <div class="mb-2">
                                    <strong>Deadline:</strong>
                                    <span class="float-end {{ $task->deadline->isPast() && $task->status != 'completed' ? 'text-danger' : '' }}">
                                        {{ $task->deadline->format('M d, Y H:i') }}
                                    </span>
                                </div>
                                
                                <div class="mb-2">
                                    <strong>Assigned To:</strong>
                                    <span class="float-end">
                                        {{ $task->assignedUser ? $task->assignedUser->name : 'Not assigned' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h5>Documents</h5>
                        
                        <div class="card mb-4">
                            <div class="card-body">
                                <form action="{{ route('tasks.uploadDocument', $task->id) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                                    @csrf
                                    <div class="col-auto">
                                        <input type="file" class="form-control @error('document') is-invalid @enderror" 
                                               id="document" name="document" required>
                                        @error('document')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">Upload Document</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        @if($task->documents->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Document Name</th>
                                            <th>Uploaded By</th>
                                            <th>Upload Date</th>
                                            <th>Size</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($task->documents as $document)
                                        <tr>
                                            <td>{{ $document->original_name }}</td>
                                            <td>{{ $document->user->name }}</td>
                                            <td>{{ $document->created_at->format('M d, Y H:i') }}</td>
                                            <td>{{ round($document->file_size / 1024, 2) }} KB</td>
                                            <td>
                                                <a href="{{ route('documents.download', $document->id) }}" class="btn btn-sm btn-success">Download</a>
                                                
                                                @if(Auth::user()->isAdmin() || Auth::user()->id == $document->user_id)
                                                <form action="{{ route('documents.delete', $document->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this document?')">Delete</button>
                                                </form>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                No documents attached to this task.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection