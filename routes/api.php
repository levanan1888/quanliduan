<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\RbacController;

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

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Example protected routes with Spatie Permissions
    Route::get('/admin/overview', function () {
        return response()->json(['message' => 'Admin content']);
    })->middleware(\Spatie\Permission\Middleware\RoleMiddleware::class . ':admin');

    Route::get('/reports/export', function () {
        return response()->json(['message' => 'Exported']);
    })->middleware(\Spatie\Permission\Middleware\PermissionMiddleware::class . ':export reports');

    // RBAC admin routes
    Route::middleware(\Spatie\Permission\Middleware\RoleMiddleware::class . ':admin')->group(function () {
        // roles
        Route::get('/roles', [RbacController::class, 'listRoles']);
        Route::post('/roles', [RbacController::class, 'createRole']);
        Route::delete('/roles/{role}', [RbacController::class, 'deleteRole']);

        // permissions
        Route::get('/permissions', [RbacController::class, 'listPermissions']);
        Route::post('/permissions', [RbacController::class, 'createPermission']);
        Route::delete('/permissions/{permission}', [RbacController::class, 'deletePermission']);

        // assignments: user ↔ role/permission
        Route::post('/users/{userId}/roles', [RbacController::class, 'assignRoleToUser']);
        Route::delete('/users/{userId}/roles/{role}', [RbacController::class, 'removeRoleFromUser']);

        Route::post('/users/{userId}/permissions', [RbacController::class, 'assignPermissionToUser']);
        Route::delete('/users/{userId}/permissions/{permission}', [RbacController::class, 'revokePermissionFromUser']);

        // assignments: role ↔ permission
        Route::post('/roles/{role}/permissions', [RbacController::class, 'assignPermissionToRole']);
        Route::delete('/roles/{role}/permissions/{permission}', [RbacController::class, 'revokePermissionFromRole']);

        // lookups
        Route::get('/users/{userId}/roles', [RbacController::class, 'userRoles']);
        Route::get('/users/{userId}/permissions', [RbacController::class, 'userPermissions']);
    });
});
