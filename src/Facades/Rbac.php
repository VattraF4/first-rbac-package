<?php

declare(strict_types=1);

namespace Vattra\Rbac\Facades;

use Illuminate\Support\Facades\Facade;
use Vattra\Rbac\RbacManager;

/**
 * @method static void register()
 * @method static \Vattra\Rbac\Models\Role|null findRole(string $name)
 * @method static \Vattra\Rbac\Models\Permission|null findPermission(string $name)
 * @method static bool permissionExists(string $name)
 * @method static class-string userModel()
 * @method static void flushCache()
 *
 * @see \Vattra\Rbac\RbacManager
 */
class Rbac extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RbacManager::class;
    }
}
