<?php

declare(strict_types=1);

namespace Vattra\Rbac\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\RbacManager;

trait HasRolesAndPermissions
{
    /** Request-scoped caches */
    protected ?Collection $cachedPermissions = null;
    protected ?Collection $cachedRoles = null;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            RbacManager::table('user_roles'),
            RbacManager::userForeignKey(),
            'role_id'
        );
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            RbacManager::table('user_permissions'),
            RbacManager::userForeignKey(),
            'permission_id'
        );
    }

    /* ------------------------------------------------------------------ *
     | Queries
     * ------------------------------------------------------------------ */

    /** @return Collection<int, array{name: string, type: string}> */
    protected function getCachedRoles(): Collection
    {
        return $this->cachedRoles ??= collect(
            app(RbacManager::class)->remember($this->rbacCacheKey('roles'), function (): array {
                return $this->roles()->get()
                    ->map(fn (Role $role) => ['name' => $role->name, 'type' => $role->type])
                    ->all();
            })
        );
    }

    /** All permission names (from roles + direct) */
    public function getAllPermissions(): Collection
    {
        return $this->cachedPermissions ??= collect(
            app(RbacManager::class)->remember($this->rbacCacheKey('permissions'), function (): array {
                return $this->roles()
                    ->with('permissions')
                    ->get()
                    ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
                    ->merge($this->directPermissions()->pluck('name'))
                    ->unique()
                    ->values()
                    ->all();
            })
        );
    }

    public function getRoleNames(): Collection
    {
        return $this->getCachedRoles()->pluck('name')->values();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RbacManager::superAdminRole());
    }

    public function hasPermissionTo(string $permission): bool
    {
        return $this->isSuperAdmin() || $this->getAllPermissions()->contains($permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return $this->isSuperAdmin()
            || $this->getAllPermissions()->intersect($permissions)->isNotEmpty();
    }

    public function hasAllPermissions(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = array_values(array_unique($permissions));

        return count($permissions) === $this->getAllPermissions()->intersect($permissions)->count();
    }

    /** True when the user has ANY of the given roles. */
    public function hasRole(string ...$names): bool
    {
        return $this->getRoleNames()->intersect($names)->isNotEmpty();
    }

    public function hasAnyRole(string ...$names): bool
    {
        return $this->hasRole(...$names);
    }

    /** True when the user has EVERY one of the given roles. */
    public function hasAllRoles(string ...$names): bool
    {
        $names = array_values(array_unique($names));

        return $names !== [] && count($names) === $this->getRoleNames()->intersect($names)->count();
    }

    public function hasRoleType(string ...$types): bool
    {
        return $this->getCachedRoles()->pluck('type')->intersect($types)->isNotEmpty();
    }

    /* ------------------------------------------------------------------ *
     | Mutations
     * ------------------------------------------------------------------ */

    public function assignRole(string ...$names): static
    {
        $this->roles()->syncWithoutDetaching($this->resolveRoleIds($names));
        $this->flushCache();

        return $this;
    }

    public function removeRole(string ...$names): static
    {
        $this->roles()->detach(Role::whereIn('name', $names)->pluck('id')->all());
        $this->flushCache();

        return $this;
    }

    public function syncRoles(array $names): static
    {
        $this->roles()->sync(Role::whereIn('name', $names)->pluck('id'));
        $this->flushCache();

        return $this;
    }

    public function givePermissionTo(string ...$permissions): static
    {
        $ids = Permission::whereIn('name', $permissions)->pluck('id');

        if ($ids->count() !== count(array_unique($permissions))) {
            throw new InvalidArgumentException('One or more permissions do not exist.');
        }

        $this->directPermissions()->syncWithoutDetaching($ids);
        $this->flushCache();

        return $this;
    }

    public function revokePermissionTo(string ...$permissions): static
    {
        $this->directPermissions()->detach(Permission::whereIn('name', $permissions)->pluck('id')->all());
        $this->flushCache();

        return $this;
    }

    public function flushCache(): void
    {
        $manager = app(RbacManager::class);
        $manager->forget($this->rbacCacheKey('roles'));
        $manager->forget($this->rbacCacheKey('permissions'));

        $this->cachedPermissions = null;
        $this->cachedRoles = null;
        $this->unsetRelation('roles');
    }

    /* ------------------------------------------------------------------ *
     | Scopes: User::role('admin')->get(), User::permission('x|y')->get()
     * ------------------------------------------------------------------ */

    /** @param  array<int, string>|string  $roles */
    public function scopeRole(Builder $query, array|string $roles): Builder
    {
        $roles = RbacManager::parseList($roles);
        $column = RbacManager::table('roles') . '.name';

        return $query->whereHas('roles', fn (Builder $q) => $q->whereIn($column, $roles));
    }

    /** Users holding the permission via a role, directly, or as super admin. */
    public function scopePermission(Builder $query, array|string $permissions): Builder
    {
        $permissions = RbacManager::parseList($permissions);
        $roleName = RbacManager::table('roles') . '.name';
        $permissionName = RbacManager::table('permissions') . '.name';

        return $query->where(function (Builder $outer) use ($permissions, $roleName, $permissionName) {
            $outer->whereHas('roles', function (Builder $roles) use ($permissions, $roleName, $permissionName) {
                $roles->where($roleName, RbacManager::superAdminRole())
                    ->orWhereHas('permissions', fn (Builder $p) => $p->whereIn($permissionName, $permissions));
            })->orWhereHas('directPermissions', fn (Builder $p) => $p->whereIn($permissionName, $permissions));
        });
    }

    /* ------------------------------------------------------------------ *
     | Internals
     * ------------------------------------------------------------------ */

    /**
     * @param  array<int, string>  $names
     * @return array<int, int>
     */
    protected function resolveRoleIds(array $names): array
    {
        $ids = Role::whereIn('name', $names)->pluck('id');

        if ($ids->count() !== count(array_unique($names))) {
            throw new InvalidArgumentException('One or more roles do not exist.');
        }

        return $ids->all();
    }

    protected function rbacCacheKey(string $suffix): string
    {
        return "user:{$this->getTable()}:{$this->getKey()}:{$suffix}";
    }
}
