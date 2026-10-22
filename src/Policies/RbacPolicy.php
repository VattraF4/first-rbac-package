<?php

namespace Vattra\Rbac\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

abstract class RbacPolicy
{
    use HandlesAuthorization;

    /**
     * Check if the user has the general permission to perform this action.
     */
    public function before($user, $ability)
    {
        // Super admin bypasses all policies
        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }
    }

    /**
     * Helper: Check if user has permission for this module.
     */
    public function hasPermission($user, string $permission): bool
    {
        return method_exists($user, 'hasPermissionTo') 
            ? $user->hasPermissionTo($permission) 
            : false;
    }
}