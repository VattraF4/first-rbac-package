<?php

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;
use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;

class InstallCommand extends Command
{
    protected $signature = 'rbac:install';
    protected $description = 'Seed roles and permissions from config/rbac.php';

    public function handle(): int
    {
        $protected = collect(config('rbac.rbac_protected_permissions', []));

        // 1. Permissions — collect from every listed role
        $permissions = collect(config('rbac.roles'))
            ->flatMap(fn ($r) => (array) ($r['permissions'] ?? []))
            ->reject(fn ($p) => in_array($p, ['*','*except_rbac']))
            ->merge($protected)
            ->unique();

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], [
                'group' => str_contains($name, '_') ? explode('_', $name)[1] : null,
            ]);
        }

        $all = Permission::pluck('id', 'name');

        // 2. Roles
        foreach (config('rbac.roles') as $name => $def) {
            $role = Role::firstOrCreate(['name' => $name], [
                'type' => $def['type'],
                'is_locked' => $def['locked'] ?? false,
            ]);

            $perms = match ($def['permissions']) {
                '*'             => $all->keys(),
                '*except_rbac'  => $all->keys()->diff($protected),
                default         => $def['permissions'],
            };

            $role->permissions()->sync(
                Permission::whereIn('name', $perms)->pluck('id')
            );

            $this->line("  {$name} -> ".count($perms)." permissions");
        }

        $this->info('RBAC installed.');
        return self::SUCCESS;
    }
}