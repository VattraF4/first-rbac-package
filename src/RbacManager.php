<?php

declare(strict_types=1);

namespace Vattra\Rbac;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\Services\PermissionRegistrar;

class RbacManager
{
    /** @var array<string, true>|null Request-scoped set of permission names */
    protected ?array $permissionNames = null;

    public function __construct(protected PermissionRegistrar $registrar)
    {
    }

    /* ------------------------------------------------------------------ *
     | Config helpers
     * ------------------------------------------------------------------ */

    public static function table(string $key): string
    {
        return (string) config("rbac.tables.{$key}", $key);
    }

    public static function userForeignKey(): string
    {
        return (string) config('rbac.user_foreign_key', 'user_id');
    }

    public static function superAdminRole(): string
    {
        return (string) config('rbac.super_admin_role', 'super_admin');
    }

    public static function adminRole(): string
    {
        return (string) config('rbac.admin_role', 'admin');
    }

    /** @return class-string */
    public function userModel(): string
    {
        return (string) (config('rbac.user_model')
            ?: config('auth.providers.users.model')
            ?: 'App\\Models\\User');
    }

    /**
     * Split "a|b" style values (and arrays of them) into a clean list.
     *
     * @param  array<int, string>|string  $values
     * @return array<int, string>
     */
    public static function parseList(array|string $values): array
    {
        $items = [];

        foreach ((array) $values as $value) {
            foreach (explode('|', (string) $value) as $part) {
                $part = trim($part);

                if ($part !== '') {
                    $items[] = $part;
                }
            }
        }

        return array_values(array_unique($items));
    }

    /* ------------------------------------------------------------------ *
     | Facade API
     * ------------------------------------------------------------------ */

    public function register(): void
    {
        $this->registrar->register();
    }

    public function findRole(string $name): ?Role
    {
        return Role::query()->where('name', $name)->first();
    }

    public function findPermission(string $name): ?Permission
    {
        return Permission::query()->where('name', $name)->first();
    }

    public function permissionExists(string $name): bool
    {
        return isset($this->permissionNames()[$name]);
    }

    /** @return array<string, true> */
    public function permissionNames(): array
    {
        return $this->permissionNames ??= $this->remember('permission-names', function (): array {
            try {
                return array_fill_keys(Permission::query()->pluck('name')->all(), true);
            } catch (QueryException) {
                return []; // tables not migrated yet
            }
        });
    }

    /* ------------------------------------------------------------------ *
     | Persistent cache
     * ------------------------------------------------------------------ */

    public function cacheEnabled(): bool
    {
        return (bool) config('rbac.cache.enabled', false);
    }

    public function remember(string $key, Closure $callback): mixed
    {
        if (! $this->cacheEnabled()) {
            return $callback();
        }

        return $this->store()->remember(
            $this->versionedKey($key),
            (int) config('rbac.cache.ttl', 3600),
            $callback
        );
    }

    public function forget(string $key): void
    {
        if ($this->cacheEnabled()) {
            $this->store()->forget($this->versionedKey($key));
        }
    }

    /** Invalidate everything: request memo + every persisted entry. */
    public function flushCache(): void
    {
        $this->permissionNames = null;

        if ($this->cacheEnabled()) {
            $this->store()->forever($this->prefix() . ':version', (string) Str::uuid());
        }
    }

    protected function store(): Repository
    {
        return Cache::store(config('rbac.cache.store'));
    }

    protected function prefix(): string
    {
        return (string) config('rbac.cache.prefix', 'rbac');
    }

    protected function versionedKey(string $key): string
    {
        $version = $this->store()->get($this->prefix() . ':version', '0');

        return "{$this->prefix()}:{$version}:{$key}";
    }
}
