<?php

// src/Traits/HasRolesAndPermissions.php
namespace Vattra\Rbac\Traits;

use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;
use Illuminate\Support\Collection;

trait HasRolesAndPermissions
{
    /** Request-scoped cache */
    protected ?Collection $cachedPermissions = null;
    protected ?Collection $cachedRoles = null;

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function directPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    protected function getCachedRoles(): Collection
    {
        return $this->cachedRoles ??= $this->roles()->get();
    }

    /** All permission names (from roles + direct) */
    public function getAllPermissions(): Collection
    {
        return $this->cachedPermissions ??= $this->roles()
            ->with('permissions:id,name')
            ->get()
            ->flatMap(fn ($r) => $r->permissions->pluck('name'))
            ->merge($this->directPermissions()->pluck('name'))
            ->unique()
            ->values();
    }

    public function hasPermissionTo(string $permission): bool
    {
        if ($this->hasRole(config('rbac.super_admin_role', 'super_admin'))) {
            return true;
        }
        return $this->getAllPermissions()->contains($permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->hasRole(config('rbac.super_admin_role', 'super_admin'))) {
            return true;
        }

        return $this->getAllPermissions()->intersect($permissions)->isNotEmpty();
    }

    public function hasRole(string ...$names): bool
    {
        return $this->getCachedRoles()->pluck('name')->intersect($names)->isNotEmpty();
    }

    public function hasRoleType(string ...$types): bool
    {
        return $this->getCachedRoles()->pluck('type')->intersect($types)->isNotEmpty();
    }

    public function assignRole(string ...$names): static
    {
        $ids = Role::whereIn('name', $names)->pluck('id');
        if ($ids->count() !== count(array_unique($names))) {
            throw new \InvalidArgumentException('One or more roles do not exist.');
        }

        $this->roles()->syncWithoutDetaching($ids);
        $this->flushCache();
        return $this;
    }

    public function syncRoles(array $names): static
    {
        $ids = Role::whereIn('name', $names)->pluck('id');
        $this->roles()->sync($ids);
        $this->flushCache();
        return $this;
    }

    public function givePermissionTo(string ...$permissions): static
    {
        $ids = Permission::whereIn('name', $permissions)->pluck('id');
        if ($ids->count() !== count(array_unique($permissions))) {
            throw new \InvalidArgumentException('One or more permissions do not exist.');
        }

        $this->directPermissions()->syncWithoutDetaching($ids);
        $this->flushCache();
        return $this;
    }

    public function flushCache(): void
    {
        $this->cachedPermissions = null;
        $this->cachedRoles = null;
        $this->unsetRelation('roles');
    }
}