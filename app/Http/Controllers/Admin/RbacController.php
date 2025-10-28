<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RbacController extends Controller
{
    public function listRoles()
    {
        return response()->json(Role::all());
    }

    public function createRole(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:255']]);
        $role = Role::firstOrCreate(['name' => $data['name']]);
        return response()->json($role, 201);
    }

    public function deleteRole(string $role)
    {
        $model = Role::where('name', $role)->first();
        if (! $model) return response()->json(['message' => 'Not found'], 404);
        $model->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function listPermissions()
    {
        return response()->json(Permission::all());
    }

    public function createPermission(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:255']]);
        $perm = Permission::firstOrCreate(['name' => $data['name']]);
        return response()->json($perm, 201);
    }

    public function deletePermission(string $permission)
    {
        $model = Permission::where('name', $permission)->first();
        if (! $model) return response()->json(['message' => 'Not found'], 404);
        $model->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function assignRoleToUser(Request $request, int $userId)
    {
        $data = $request->validate(['role' => ['required','string']]);
        $user = User::findOrFail($userId);
        $user->assignRole($data['role']);
        return response()->json(['message' => 'Assigned']);
    }

    public function removeRoleFromUser(int $userId, string $role)
    {
        $user = User::findOrFail($userId);
        $user->removeRole($role);
        return response()->json(['message' => 'Removed']);
    }

    public function assignPermissionToUser(Request $request, int $userId)
    {
        $data = $request->validate(['permission' => ['required','string']]);
        $user = User::findOrFail($userId);
        $user->givePermissionTo($data['permission']);
        return response()->json(['message' => 'Assigned']);
    }

    public function revokePermissionFromUser(int $userId, string $permission)
    {
        $user = User::findOrFail($userId);
        $user->revokePermissionTo($permission);
        return response()->json(['message' => 'Revoked']);
    }

    public function assignPermissionToRole(Request $request, string $role)
    {
        $data = $request->validate(['permission' => ['required','string']]);
        $roleModel = Role::where('name', $role)->firstOrFail();
        $roleModel->givePermissionTo($data['permission']);
        return response()->json(['message' => 'Assigned']);
    }

    public function revokePermissionFromRole(string $role, string $permission)
    {
        $roleModel = Role::where('name', $role)->firstOrFail();
        $roleModel->revokePermissionTo($permission);
        return response()->json(['message' => 'Revoked']);
    }

    public function userRoles(int $userId)
    {
        $user = User::findOrFail($userId);
        return response()->json($user->roles);
    }

    public function userPermissions(int $userId)
    {
        $user = User::findOrFail($userId);
        return response()->json($user->permissions);
    }
}


