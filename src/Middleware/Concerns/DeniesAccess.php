<?php

declare(strict_types=1);

namespace Vattra\Rbac\Middleware\Concerns;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait DeniesAccess
{
    /**
     * Guests: 401 for JSON / apps without a login route, otherwise Laravel's
     * standard redirect to the login page.
     */
    protected function denyGuest(Request $request): never
    {
        if ($request->expectsJson() || ! Route::has('login')) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        throw new AuthenticationException('Unauthenticated.');
    }

    protected function deny(string $message): never
    {
        throw new HttpException(403, $message);
    }

    protected function usesRbac(object $user): bool
    {
        return method_exists($user, 'hasRole') && method_exists($user, 'hasAnyPermission');
    }
}
