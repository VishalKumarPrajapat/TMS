<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">Task Manager</a>
            <div class="navbar-nav ms-auto">

                @auth
                <?php
                    $userName = Auth::user()->name;
                    $isAdmin = Auth::user()->isAdmin();
                    $isManager = Auth::user()->isManager();
                    
                    ?>
                <span class="navbar-text me-3">Welcome, {{ $userName }}</span>
                <a class="nav-link" href="{{ route('tasks.index') }}">Tasks</a>
                @if ($isAdmin )
                <a class="nav-link" href="{{ route('admin.dashboard') }}">Admin Panel</a>
                @endif
                @if ( $isManager )
                <a class="nav-link" href="{{ route('manager.dashboard') }}">Manager Panel</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="nav-link btn btn-link">Logout</button>
                </form>
                @else
                <a class="nav-link" href="{{ route('login') }}">Login</a>
                <a class="nav-link" href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
        @endif

        @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>