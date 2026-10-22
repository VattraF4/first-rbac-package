<?php
// src/Middleware/RoleMiddleware.php
namespace Vattra\Rbac\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (! $request->user() || ! $request->user()->hasRole(...$roles)) {
            throw new HttpException(403, 'Role not allowed.');
        }
        return $next($request);
    }
}