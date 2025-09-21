<?php

use App\Http\Controllers\TaskController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ManagerController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/**  Public routes */
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Auth::routes();

/**Home route */
Route::get('/home', [HomeController::class, 'index'])->name('home');

/** Authenticated routes for Task */
Route::middleware(['auth'])->group(function () {
    Route::resource('tasks', TaskController::class);
    Route::post('tasks/{task}/upload-document', [TaskController::class, 'uploadDocument'])->name('tasks.uploadDocument');
    Route::get('documents/{document}/download', [TaskController::class, 'downloadDocument'])->name('documents.download');
    Route::delete('documents/{document}', [TaskController::class, 'deleteDocument'])->name('documents.delete');
});

/** Admin ROute */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
});

/** Manager routes */
Route::middleware(['auth', 'manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [ManagerController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [ManagerController::class, 'users'])->name('users.index');
    Route::get('/users/create', [ManagerController::class, 'createUser'])->name('users.create');
    Route::post('/users', [ManagerController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [ManagerController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [ManagerController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [ManagerController::class, 'deleteUser'])->name('users.delete');
});

/** Fallback route for 404 errors */
Route::fallback(function () {
    if (Auth::check()) {
        return redirect()->route('home');
    }
    return redirect()->route('welcome');
});
