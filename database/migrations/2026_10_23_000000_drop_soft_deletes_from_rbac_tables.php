<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Upgrade migration for installs created before 0.2.0, when roles and
// permissions were soft-deleted. Soft-deleted rows kept their unique names,
// which blocked re-creating a role or permission. Fresh installs: no-op.
return new class extends Migration
{
    public function up(): void
    {
        foreach (['roles', 'permissions'] as $key) {
            $table = (string) config("rbac.tables.{$key}", $key);

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
                continue;
            }

            DB::table($table)->whereNotNull('deleted_at')->delete();

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: soft deletes are no longer supported.
    }
};
