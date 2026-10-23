<?php

declare(strict_types=1);

namespace Vattra\Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vattra\Rbac\Middleware\Concerns\DeniesAccess;
use Vattra\Rbac\RbacManager;

class PermissionMiddleware
{
    use DeniesAccess;

    /** Usage: rbac.permission:view_posts,store_posts  or  rbac.permission:a|b */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user === null) {
            $this->denyGuest($request);
        }

        if (
            ! $this->usesRbac($user)
            || ! $user->hasAnyPermission(RbacManager::parseList($permissions))
        ) {
            $this->deny('You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
