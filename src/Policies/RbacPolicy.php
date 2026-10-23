<?php

declare(strict_types=1);

namespace Vattra\Rbac\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Vattra\Rbac\RbacManager;

abstract class RbacPolicy
{
    use HandlesAuthorization;

    /**
     * The super admin bypasses every policy method. Guests are skipped
     * because $user is non-nullable.
     */
    public function before(Authenticatable $user, string $ability): ?bool
    {
        if (method_exists($user, 'hasRole') && $user->hasRole(RbacManager::superAdminRole())) {
            return true;
        }

        return null;
    }

    /** Helper: does the user hold this permission? */
    public function hasPermission(?Authenticatable $user, string $permission): bool
    {
        return $user !== null
            && method_exists($user, 'hasPermissionTo')
            && $user->hasPermissionTo($permission);
    }
}
