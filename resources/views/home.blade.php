@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Dashboard</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <h4>Welcome to Task Management System, {{ $userName }}!</h4>

                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card text-white bg-primary mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">My Tasks</h5>
                                        <p class="card-text">
                                            {{ $createTasks }} tasks created
                                        </p>
                                        <a href="{{ route('tasks.index') }}" class="btn btn-light">View Tasks</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card text-white bg-success mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Assigned Tasks</h5>
                                        <p class="card-text">
                                            {{ $assignedTasks }} tasks assigned to me
                                        </p>
                                        <a href="{{ route('tasks.index') }}" class="btn btn-light">View Tasks</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card text-white bg-info mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title">Completed</h5>
                                        <p class="card-text">
                                            {{ $completeTask }} tasks
                                            completed
                                        </p>
                                        <a href="{{ route('tasks.index') }}" class="btn btn-light">View Tasks</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($isAdmin || $isMAnager)
                            <div class="mt-4">
                                <h5>Quick Actions</h5>
                                <div class="d-grid gap-2 d-md-block">
                                    <a href="{{ route('tasks.create') }}" class="btn btn-primary">Create New Task</a>
                                    <a href="{{ route('tasks.index') }}" class="btn btn-secondary">View All Tasks</a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
