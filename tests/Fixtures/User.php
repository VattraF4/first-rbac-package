<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Vattra\Rbac\Traits\HasRolesAndPermissions;

class User extends Authenticatable
{
    use HasRolesAndPermissions;

    protected $table = 'users';

    protected $guarded = [];
}
