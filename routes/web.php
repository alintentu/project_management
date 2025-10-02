<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlanningController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskAssigneeController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WorkBreakdownStructureController;
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

Route::get('/planning', PlanningController::class)
    ->middleware(['auth', 'verified'])
    ->name('planning');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware('can:user.manage')
        ->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->middleware('can:user.manage')
        ->name('users.store');

    Route::post('/projects', [ProjectController::class, 'store'])
        ->middleware('can:project.create')
        ->name('projects.store');

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
    Route::patch('/tasks/{task}/schedule', [TaskController::class, 'updateSchedule'])
        ->middleware('can:task.update')
        ->name('tasks.schedule');

    Route::post('/wbs', [WorkBreakdownStructureController::class, 'store'])
        ->middleware('can:project.update')
        ->name('wbs.store');
    Route::put('/wbs/{wbs}', [WorkBreakdownStructureController::class, 'update'])
        ->middleware('can:project.update')
        ->name('wbs.update');
    Route::delete('/wbs/{wbs}', [WorkBreakdownStructureController::class, 'destroy'])
        ->middleware('can:project.update')
        ->name('wbs.destroy');
});

require __DIR__.'/auth.php';
