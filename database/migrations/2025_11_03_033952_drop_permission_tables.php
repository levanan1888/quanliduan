<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop Spatie permission tables as we're using simple role-based authorization instead.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            return;
        }

        Schema::dropIfExists($tableNames['role_has_permissions'] ?? 'role_has_permissions');
        Schema::dropIfExists($tableNames['model_has_roles'] ?? 'model_has_roles');
        Schema::dropIfExists($tableNames['model_has_permissions'] ?? 'model_has_permissions');
        Schema::dropIfExists($tableNames['roles'] ?? 'roles');
        Schema::dropIfExists($tableNames['permissions'] ?? 'permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This would require recreating the permission tables
        // For now, we'll leave this empty as dropping permission system is intentional
    }
};

