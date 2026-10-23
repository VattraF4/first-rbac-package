<?php

declare(strict_types=1);

namespace Vattra\Rbac\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Vattra\Rbac\RbacManager;

class PermissionRegistrar
{
    protected bool $registered = false;

    public function isRegistered(): bool
    {
        return $this->registered;
    }

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;

        // The closure's non-nullable `Authenticatable` type makes Laravel skip
        // it for guests instead of calling it with null.
        Gate::before(function (Authenticatable $user, string $ability, array $arguments = []) {
            return $this->resolve($user, $ability, $arguments);
        });

        if (config('rbac.register_role_gates', true)) {
            foreach (array_keys((array) config('rbac.roles', [])) as $role) {
                Gate::define(
                    'is_' . $role,
                    fn (Authenticatable $user): bool => $this->userHasRole($user, (string) $role)
                );
            }
        }

        Gate::define('is_admin', fn (Authenticatable $user): bool => $this->userHasRole(
            $user,
            RbacManager::superAdminRole(),
            RbacManager::adminRole()
        ));

        Gate::define('is_staff', fn (Authenticatable $user): bool => method_exists($user, 'hasRoleType')
            && $user->hasRoleType('system', 'admin', 'staff'));
    }

    /**
     * Grant-only hook: returns true or null, never false, so normal gates and
     * policies keep the final say.
     *
     * @param  array<int, mixed>  $arguments
     */
    protected function resolve(Authenticatable $user, string $ability, array $arguments): ?bool
    {
        if (! method_exists($user, 'hasPermissionTo') || ! method_exists($user, 'hasRole')) {
            return null;
        }

        // Never short-circuit a policy; it decides (see RbacPolicy::before()).
        if ($this->hasPolicyFor($arguments)) {
            return null;
        }

        if ($user->hasRole(RbacManager::superAdminRole())) {
            return true;
        }

        if (app(RbacManager::class)->permissionExists($ability) && $user->hasPermissionTo($ability)) {
            return true;
        }

        return null;
    }

    /** @param  array<int, mixed>  $arguments */
    protected function hasPolicyFor(array $arguments): bool
    {
        $subject = $arguments[0] ?? null;

        if ($subject === null) {
            return false;
        }

        return Gate::getPolicyFor($subject) !== null;
    }

    protected function userHasRole(Authenticatable $user, string ...$roles): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole(...$roles);
    }
}
