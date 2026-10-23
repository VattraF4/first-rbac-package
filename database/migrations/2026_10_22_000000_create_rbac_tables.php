<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ---------------------------------------------------------
        // 1. ROLES
        // ---------------------------------------------------------
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('staff')->index();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // ---------------------------------------------------------
        // 2. PERMISSIONS
        // ---------------------------------------------------------
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('group')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // ---------------------------------------------------------
        // 3. ROLE_PERMISSIONS (pivot: role ↔ permission)
        // ---------------------------------------------------------
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();
            $table->foreignId('permission_id')
                  ->constrained('permissions')
                  ->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        // ---------------------------------------------------------
        // 4. USER_ROLES (pivot: user ↔ role)
        // ---------------------------------------------------------
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->constrained(config('rbac.user_table', 'users'))
                  ->cascadeOnDelete();
            $table->foreignId('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        // ---------------------------------------------------------
        // 5. USER_PERMISSIONS (pivot: user ↔ permission, direct grants)
        // ---------------------------------------------------------
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->constrained(config('rbac.user_table', 'users'))
                  ->cascadeOnDelete();
            $table->foreignId('permission_id')
                  ->constrained('permissions')
                  ->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     * Order is critical: pivots first, then parents.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};