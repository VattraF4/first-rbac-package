<?php

namespace Vattra\Rbac\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register()
 * @method static \Vattra\Rbac\Models\Role|null findRole(string $name)
 * @method static \Vattra\Rbac\Models\Permission|null findPermission(string $name)
 *
 * @see \Vattra\Rbac\Services\PermissionRegistrar
 */
class Rbac extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Vattra\Rbac\Services\PermissionRegistrar::class;
    }
}