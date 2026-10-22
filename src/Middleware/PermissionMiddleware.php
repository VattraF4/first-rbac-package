<?php
// src/Middleware/PermissionMiddleware.php
namespace Vattra\Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions)
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyPermission($permissions)) {
            throw new HttpException(403, 'You do not have permission to access this resource.');
        }
        return $next($request);
    }
}