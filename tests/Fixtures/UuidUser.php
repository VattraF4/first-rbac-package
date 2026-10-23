<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Fixtures;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Vattra\Rbac\Traits\HasRolesAndPermissions;

class UuidUser extends Authenticatable
{
    use HasRolesAndPermissions;
    use HasUuids;

    protected $table = 'uuid_users';

    protected $guarded = [];
}
