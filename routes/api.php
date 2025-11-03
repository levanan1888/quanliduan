<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SprintController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\SubTaskController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/refresh', [AuthController::class, 'refresh']); // No auth needed, uses refresh_token

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Projects (PM → Project)
    Route::apiResource('projects', ProjectController::class);

    // Sprints (Project → Sprint)
    Route::prefix('projects/{project}')->group(function () {
        Route::apiResource('sprints', SprintController::class);
    });

    // Tasks
    Route::apiResource('tasks', TaskController::class);
    Route::post('/tasks/{task}/assets', [TaskController::class, 'uploadAsset']);
    Route::get('/tasks/{task}/activities', [TaskController::class, 'activities']);

    // SubTasks (Task → SubTask)
    Route::prefix('tasks/{task}')->group(function () {
        Route::apiResource('sub-tasks', SubTaskController::class)->except(['show']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{notification}', [NotificationController::class, 'destroy']);
    });
});
