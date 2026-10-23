<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Feature;

use InvalidArgumentException;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\Tests\Fixtures\User;
use Vattra\Rbac\Tests\TestCase;

class HasRolesAndPermissionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->installRbac();
    }

    public function testAssignCheckAndRemoveRoles(): void
    {
        $user = $this->makeUser('editor');

        $this->assertTrue($user->hasRole('editor'));
        $this->assertTrue($user->hasRole('admin', 'editor'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertSame(['editor'], $user->getRoleNames()->all());

        $user->assignRole('user');
        $this->assertTrue($user->hasAllRoles('editor', 'user'));
        $this->assertFalse($user->hasAllRoles('editor', 'admin'));
        $this->assertTrue($user->hasAnyRole('admin', 'user'));

        $user->removeRole('editor');
        $this->assertFalse($user->hasRole('editor'));
        $this->assertSame(['user'], $user->getRoleNames()->all());
    }

    public function testAssigningUnknownRoleThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeUser()->assignRole('ghost');
    }

    public function testSyncRolesReplacesExistingRoles(): void
    {
        $user = $this->makeUser('editor', 'user');

        $user->syncRoles(['admin']);

        $this->assertSame(['admin'], $user->getRoleNames()->all());
    }

    public function testRolePermissionsAndDirectPermissions(): void
    {
        $user = $this->makeUser('user');

        $this->assertTrue($user->hasPermissionTo('view_posts'));
        $this->assertFalse($user->hasPermissionTo('destroy_posts'));

        $user->givePermissionTo('destroy_posts');
        $this->assertTrue($user->hasPermissionTo('destroy_posts'));
        $this->assertTrue($user->hasAnyPermission(['nope', 'destroy_posts']));
        $this->assertTrue($user->hasAllPermissions(['view_posts', 'destroy_posts']));
        $this->assertFalse($user->hasAllPermissions(['view_posts', 'update_posts']));

        $user->revokePermissionTo('destroy_posts');
        $this->assertFalse($user->hasPermissionTo('destroy_posts'));
    }

    public function testGivingUnknownPermissionThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->makeUser()->givePermissionTo('ghost');
    }

    public function testSuperAdminHasEveryPermissionAndRoleCheckOfPermissions(): void
    {
        $super = $this->makeUser('super_admin');

        $this->assertTrue($super->hasPermissionTo('anything_at_all'));
        $this->assertTrue($super->hasAnyPermission(['x']));
        $this->assertTrue($super->hasAllPermissions(['x', 'y']));
        $this->assertTrue($super->isSuperAdmin());
    }

    public function testAdminRoleGetsEverythingExceptRbacPermissions(): void
    {
        $admin = $this->makeUser('admin');

        $this->assertTrue($admin->hasPermissionTo('view_posts'));
        $this->assertFalse($admin->hasPermissionTo('assign_permissions'));
    }

    public function testHasRoleType(): void
    {
        $this->assertTrue($this->makeUser('editor')->hasRoleType('staff'));
        $this->assertFalse($this->makeUser('user')->hasRoleType('staff'));
    }

    public function testScopes(): void
    {
        $editor = $this->makeUser('editor');
        $plain = $this->makeUser('user');
        $super = $this->makeUser('super_admin');
        $direct = $this->makeUser();
        $direct->givePermissionTo('destroy_posts');

        $this->assertEqualsCanonicalizing(
            [$editor->id],
            User::role('editor')->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$editor->id, $plain->id],
            User::role('editor|user')->pluck('id')->all()
        );

        $withDestroy = User::permission('destroy_posts')->pluck('id')->all();
        $this->assertContains($editor->id, $withDestroy);
        $this->assertContains($direct->id, $withDestroy);
        $this->assertContains($super->id, $withDestroy);
        $this->assertNotContains($plain->id, $withDestroy);
    }

    public function testLockedRolesCannotBeDeleted(): void
    {
        $this->expectExceptionMessage('locked');

        Role::where('name', 'super_admin')->first()->delete();
    }

    public function testUnlockedRoleIsHardDeletedAndNameCanBeReused(): void
    {
        Role::where('name', 'editor')->first()->delete();
        $this->assertDatabaseMissing('roles', ['name' => 'editor']);

        Role::create(['name' => 'editor', 'type' => 'staff']);
        $this->assertSame(1, Role::where('name', 'editor')->count());
    }

    public function testPersistentCacheIsFlushedWhenRolePermissionsChange(): void
    {
        config(['rbac.cache.enabled' => true, 'cache.default' => 'array']);

        $user = $this->makeUser('editor');

        // Warm the persistent cache with a fresh instance.
        $this->assertFalse(User::find($user->id)->hasPermissionTo('store_comments'));

        Role::where('name', 'editor')->first()->givePermissionTo('store_comments');

        $this->assertTrue(User::find($user->id)->hasPermissionTo('store_comments'));
    }

    public function testPersistentCacheIsFlushedWhenUserRolesChange(): void
    {
        config(['rbac.cache.enabled' => true, 'cache.default' => 'array']);

        $user = $this->makeUser('user');
        $this->assertFalse(User::find($user->id)->hasRole('editor'));

        $user->assignRole('editor');

        $this->assertTrue(User::find($user->id)->hasRole('editor'));
    }
}
