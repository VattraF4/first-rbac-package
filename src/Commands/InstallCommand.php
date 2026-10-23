<?php

declare(strict_types=1);

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\RbacManager;

class InstallCommand extends Command
{
    protected $signature = 'rbac:install';
    protected $description = 'Seed roles and permissions from config/rbac.php';

    public function handle(RbacManager $manager): int
    {
        $roles = (array) config('rbac.roles', []);
        $protected = collect((array) config('rbac.rbac_protected_permissions', []));

        // 1. Permissions: collect from every listed role
        $permissions = collect($roles)
            ->flatMap(fn ($role) => (array) ($role['permissions'] ?? []))
            ->reject(fn ($name) => in_array($name, ['*', '*except_rbac'], true))
            ->merge($protected)
            ->unique()
            ->values();

        DB::transaction(function () use ($permissions, $roles, $protected) {
            foreach ($permissions as $name) {
                Permission::firstOrCreate(['name' => $name], [
                    // "view_blog_posts" => "posts" (last segment)
                    'group' => str_contains($name, '_') ? Str::afterLast($name, '_') : null,
                ]);
            }

            $all = Permission::pluck('id', 'name');

            // 2. Roles
            foreach ($roles as $name => $definition) {
                $role = Role::firstOrCreate(['name' => $name], [
                    'type' => $definition['type'] ?? 'staff',
                    'is_locked' => $definition['locked'] ?? false,
                ]);

                $configured = $definition['permissions'] ?? [];

                $names = match ($configured) {
                    '*' => $all->keys()->all(),
                    '*except_rbac' => $all->keys()->diff($protected)->values()->all(),
                    default => (array) $configured,
                };

                $role->permissions()->sync(Permission::whereIn('name', $names)->pluck('id'));

                $this->line("  {$name} -> " . count($names) . ' permissions');
            }
        });

        $manager->flushCache();

        $this->info('RBAC installed.');

        return self::SUCCESS;
    }
}
