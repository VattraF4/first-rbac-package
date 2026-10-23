<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/** A user model WITHOUT the RBAC trait. */
class PlainUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
