<?php

declare(strict_types=1);

namespace Vattra\Rbac\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RuntimeException;
use Vattra\Rbac\RbacManager;

class Role extends Model
{
    protected $fillable = ['name', 'type', 'description', 'is_locked'];

    protected $casts = ['is_locked' => 'boolean'];

    public function getTable(): string
    {
        return RbacManager::table('roles');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            RbacManager::table('role_permissions'),
            'role_id',
            'permission_id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            app(RbacManager::class)->userModel(),
            RbacManager::table('user_roles'),
            'role_id',
            RbacManager::userForeignKey()
        );
    }

    /** @param  array<int, string>|string  ...$permissions */
    public function givePermissionTo(array|string ...$permissions): static
    {
        $this->permissions()->syncWithoutDetaching($this->permissionIds($permissions));
        app(RbacManager::class)->flushCache();

        return $this;
    }

    /** @param  array<int, string>|string  ...$permissions */
    public function revokePermissionTo(array|string ...$permissions): static
    {
        $this->permissions()->detach($this->permissionIds($permissions));
        app(RbacManager::class)->flushCache();

        return $this;
    }

    /** @param  array<int, string>  $permissions */
    public function syncPermissions(array $permissions): static
    {
        $this->permissions()->sync($this->permissionIds([$permissions]));
        app(RbacManager::class)->flushCache();

        return $this;
    }

    /**
     * @param  array<int, array<int, string>|string>  $names
     * @return array<int, int>
     */
    protected function permissionIds(array $names): array
    {
        $flat = [];
        array_walk_recursive($names, function ($name) use (&$flat) {
            $flat[] = (string) $name;
        });

        return Permission::query()
            ->whereIn('name', RbacManager::parseList($flat))
            ->pluck('id')
            ->all();
    }

    protected static function booted(): void
    {
        static::deleting(function (self $role) {
            if ($role->is_locked) {
                throw new RuntimeException("Role [{$role->name}] is locked and cannot be deleted.");
            }
        });

        $flush = fn () => app(RbacManager::class)->flushCache();

        static::saved($flush);
        static::deleted($flush);
    }
}
