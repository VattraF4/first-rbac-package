<?php

declare(strict_types=1);

namespace Vattra\Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vattra\Rbac\Middleware\Concerns\DeniesAccess;
use Vattra\Rbac\RbacManager;

class RoleMiddleware
{
    use DeniesAccess;

    /** Usage: rbac.role:admin,editor  or  rbac.role:admin|editor */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            $this->denyGuest($request);
        }

        if (! $this->usesRbac($user)) {
            $this->deny('Role not allowed.');
        }

        if (
            $user->hasRole(RbacManager::superAdminRole())
            || $user->hasRole(...RbacManager::parseList($roles))
        ) {
            return $next($request);
        }

        $this->deny('Role not allowed.');
    }
}
