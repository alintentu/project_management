<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskAssigneeController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskStatusController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::patch('/tasks/{task}/assignee', TaskAssigneeController::class)
        ->name('tasks.assignee');

    Route::patch('/tasks/{task}/status', TaskStatusController::class)
        ->name('tasks.status');

    Route::post('/tasks', [TaskController::class, 'store'])
        ->name('tasks.store');

    Route::post('/tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])
        ->name('tasks.attachments.store');
    Route::delete('/tasks/{task}/attachments/{media}', [TaskAttachmentController::class, 'destroy'])
        ->name('tasks.attachments.destroy');

    Route::get('/tasks/{task}', [TaskController::class, 'show'])
        ->name('tasks.show');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])
        ->name('tasks.update');
});

require __DIR__.'/auth.php';
