<?php

namespace Vattra\Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::pluck('id', 'name');
        $protected = config('rbac.rbac_protected_permissions', []);

        foreach (config('rbac.roles', []) as $name => $definition) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                [
                    'type' => $definition['type'] ?? 'staff',
                    'is_locked' => $definition['locked'] ?? false,
                ]
            );

            $configured = $definition['permissions'] ?? [];
            $names = match ($configured) {
                '*' => $permissions->keys(),
                '*except_rbac' => $permissions->keys()->diff($protected),
                default => collect((array) $configured),
            };

            $role->permissions()->sync(
                $permissions
                    ->filter(fn ($id, $permission) => collect($names)->contains($permission))
                    ->values()
            );
        }
    }
}
