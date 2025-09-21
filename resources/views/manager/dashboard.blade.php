@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header"><h4>Manager Dashboard</h4></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="{{ route('manager.users.index') }}" style="text-decoration: none;">
                                    <div class="card text-white bg-primary mb-3">
                                        <div class="card-body">
                                            <h5 class="card-title">Click Manage Users</h5>
                                            <p class="card-text">
                                                Total&nbsp; {{ $total_users }} users
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3">
                                <div class="card text-white bg-success mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Tasks</h5>
                                        <p class="card-text">
                                            {{ $total_task }} tasks
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card text-white bg-info mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Completed Tasks</h5>
                                        <p class="card-text">
                                            {{ $complete_Tasks }} tasks
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="card text-white bg-warning mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Pending Tasks</h5>
                                        <p class="card-text">
                                            {{ $pending_tasks }} tasks
                                        </p>
                                    </div>
                                </div>
                            </div>
 
                        </div>

                        <div class="mt-4">
                            <h4>Recent Tasks</h4>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Created By</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Deadline</th>
                                            <th>Created At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent_tasks as $task)
                                            <tr>
                                                <td>{{ $task->title }}</td>
                                                <td>{{ $task->user->name }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $task->priority === 'high' ? 'danger' : ($task->priority === 'medium' ? 'warning' : 'info') }}">
                                                        {{ $task->priority }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $task->status === 'completed' ? 'success' : ($task->status === 'in_progress' ? 'primary' : 'secondary') }}">
                                                        {{ $task->status }}
                                                    </span>
                                                </td>
                                                <td>{{ $task->deadline->format('M d, Y') }}</td>
                                                <td>{{ $task->created_at->format('M d, Y') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
