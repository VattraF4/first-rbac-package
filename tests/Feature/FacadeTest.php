<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Feature;

use Vattra\Rbac\Facades\Rbac;
use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\Tests\Fixtures\User;
use Vattra\Rbac\Tests\TestCase;

class FacadeTest extends TestCase
{
    public function testFindRoleAndPermission(): void
    {
        $this->installRbac();

        $this->assertInstanceOf(Role::class, Rbac::findRole('editor'));
        $this->assertNull(Rbac::findRole('ghost'));
        $this->assertInstanceOf(Permission::class, Rbac::findPermission('view_posts'));
        $this->assertNull(Rbac::findPermission('ghost'));
        $this->assertTrue(Rbac::permissionExists('view_posts'));
        $this->assertSame(User::class, Rbac::userModel());
    }
}
