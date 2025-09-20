@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">Task Management System</div>

                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3>Welcome to our Task Management System</h3>
                            <p class="lead">Organize, track, and manage your tasks efficiently</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Already have an account?</h5>
                                        <p class="card-text">Sign in to access your tasks and projects</p>
                                        <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card mb-3">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">New user?</h5>
                                        <p class="card-text">Create an account to start managing your tasks</p>
                                        <a href="{{ route('register') }}" class="btn btn-success">Register</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <h5>Features:</h5>
                            <ul>
                                <li>Create and manage tasks with deadlines</li>
                                <li>Set priorities and track progress</li>
                                <li>Upload documents related to tasks</li>
                                <li>Role-based access control</li>
                                <li>User-friendly interface</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
