<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Fixtures;

use Illuminate\Contracts\Auth\Authenticatable;
use Vattra\Rbac\Policies\RbacPolicy;

class PostPolicy extends RbacPolicy
{
    public function view(Authenticatable $user, Post $post): bool
    {
        return false;
    }
}
