<?php

namespace Vattra\Rbac\Database\Seeders;

use Illuminate\Database\Seeder;
use Vattra\Rbac\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $names = collect(config('rbac.roles', []))
            ->flatMap(fn ($role) => (array) ($role['permissions'] ?? []))
            ->reject(fn ($name) => in_array($name, ['*', '*except_rbac'], true))
            ->merge(config('rbac.rbac_protected_permissions', []))
            ->unique();

        foreach ($names as $name) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['group' => str_contains($name, '_') ? explode('_', $name)[1] : null]
            );
        }
    }
}
