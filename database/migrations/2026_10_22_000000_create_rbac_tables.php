<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function table(string $key): string
    {
        return (string) config("rbac.tables.{$key}", $key);
    }

    private function userKey(Blueprint $table): ForeignIdColumnDefinition
    {
        $column = (string) config('rbac.user_foreign_key', 'user_id');

        return match (config('rbac.user_key_type', 'id')) {
            'uuid' => $table->foreignUuid($column),
            'ulid' => $table->foreignUlid($column),
            default => $table->foreignId($column),
        };
    }

    public function up(): void
    {
        $roles = $this->table('roles');
        $permissions = $this->table('permissions');
        $userTable = (string) config('rbac.user_table', 'users');
        $userFk = (string) config('rbac.user_foreign_key', 'user_id');

        Schema::create($roles, function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('staff')->index();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        Schema::create($permissions, function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('group')->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create($this->table('role_permissions'), function (Blueprint $table) use ($roles, $permissions) {
            $table->foreignId('role_id')->constrained($roles)->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create($this->table('user_roles'), function (Blueprint $table) use ($roles, $userTable, $userFk) {
            $this->userKey($table)->constrained($userTable)->cascadeOnDelete();
            $table->foreignId('role_id')->constrained($roles)->cascadeOnDelete();
            $table->primary([$userFk, 'role_id']);
        });

        Schema::create($this->table('user_permissions'), function (Blueprint $table) use (
            $permissions,
            $userTable,
            $userFk
        ) {
            $this->userKey($table)->constrained($userTable)->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained($permissions)->cascadeOnDelete();
            $table->primary([$userFk, 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table('user_permissions'));
        Schema::dropIfExists($this->table('user_roles'));
        Schema::dropIfExists($this->table('role_permissions'));
        Schema::dropIfExists($this->table('permissions'));
        Schema::dropIfExists($this->table('roles'));
    }
};
