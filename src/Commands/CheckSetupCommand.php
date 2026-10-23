<?php

declare(strict_types=1);

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\RbacManager;
use Vattra\Rbac\Services\PermissionRegistrar;

class CheckSetupCommand extends Command
{
    protected $signature = 'rbac:check';
    protected $description = 'Check if the RBAC package is installed correctly';

    public function handle(RbacManager $manager, PermissionRegistrar $registrar): int
    {
        $this->info('Checking RBAC setup...');

        $problems = 0;

        // 1. Tables
        $missing = collect(['roles', 'permissions', 'role_permissions', 'user_roles', 'user_permissions'])
            ->map(fn (string $key) => RbacManager::table($key))
            ->reject(fn (string $table) => Schema::hasTable($table));

        if ($missing->isNotEmpty()) {
            $this->error('Missing tables: ' . $missing->implode(', ') . '. Run: php artisan migrate');

            return self::FAILURE; // later checks need the tables
        }

        $this->info('OK  Database tables exist.');

        $userFk = RbacManager::userForeignKey();

        foreach (['user_roles', 'user_permissions'] as $key) {
            if (! Schema::hasColumn(RbacManager::table($key), $userFk)) {
                $table = RbacManager::table($key);
                $this->error("Table [{$table}] has no [{$userFk}] column (rbac.user_foreign_key).");
                $problems++;
            }
        }

        // 2. User model + trait
        $userModel = $manager->userModel();

        if (! class_exists($userModel)) {
            $this->error("User model [{$userModel}] does not exist (rbac.user_model).");
            $problems++;
        } elseif (! method_exists($userModel, 'hasPermissionTo')) {
            $this->error("[{$userModel}] is missing the HasRolesAndPermissions trait.");
            $this->line('   Add: use Vattra\\Rbac\\Traits\\HasRolesAndPermissions;');
            $problems++;
        } else {
            $this->info('OK  User model uses the trait.');
        }

        // 3. Gate hooks
        if ($registrar->isRegistered() && Gate::has('is_admin')) {
            $this->info('OK  Gates are registered.');
        } else {
            $this->error('Gates are not registered. Is RbacServiceProvider loaded?');
            $problems++;
        }

        // 4. Roles seeded (warning only)
        $configured = array_keys((array) config('rbac.roles', []));
        $absent = array_diff($configured, Role::pluck('name')->all());

        if ($absent !== []) {
            $this->warn('Roles not in the database: ' . implode(', ', $absent) . '. Run: php artisan rbac:install');
        } else {
            $this->info('OK  Configured roles exist.');
        }

        if ($problems > 0) {
            $this->error("RBAC setup has {$problems} problem(s).");

            return self::FAILURE;
        }

        $this->info('RBAC setup looks good.');

        return self::SUCCESS;
    }
}
