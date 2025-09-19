<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Public routes
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Authentication routes
Auth::routes();

// Home route
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Authenticated routes
Route::middleware(['auth'])->group(function () {
    // Task routes
    Route::resource('tasks', TaskController::class);
    Route::post('tasks/{task}/upload-document', [TaskController::class, 'uploadDocument'])->name('tasks.uploadDocument');
    Route::get('documents/{document}/download', [TaskController::class, 'downloadDocument'])->name('documents.download');
    Route::delete('documents/{document}', [TaskController::class, 'deleteDocument'])->name('documents.delete');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // User management
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');

    // Task management
    Route::get('/tasks', [AdminController::class, 'allTasks'])->name('tasks.index');
    Route::get('/tasks/{task}', [AdminController::class, 'showTask'])->name('tasks.show');
    Route::delete('/tasks/{task}', [AdminController::class, 'deleteTask'])->name('tasks.delete');
});

// Manager routes
Route::middleware(['auth', 'manager'])->prefix('manager')->name('manager.')->group(function () {
    // Manager dashboard
    Route::get('/dashboard', [AdminController::class, 'managerDashboard'])->name('dashboard');
    Route::get('/tasks', [AdminController::class, 'teamTasks'])->name('tasks.index');
    Route::get('/tasks/{task}', [AdminController::class, 'showTeamTask'])->name('tasks.show');

    // Team tasks 
    Route::patch('/tasks/{task}/assign', [AdminController::class, 'assignTask'])->name('tasks.assign');

    // Team management
    Route::get('/team', [AdminController::class, 'teamMembers'])->name('team.index');
    Route::get('/team/performance', [AdminController::class, 'teamPerformance'])->name('team.performance');
});

// Fallback route for 404 errors
Route::fallback(function () {
    if (Auth::check()) {
        return redirect()->route('home');
    }
    return redirect()->route('welcome');
});
