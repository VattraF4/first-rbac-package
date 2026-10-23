<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Vattra\Rbac\Models\Permission;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\Tests\Fixtures\PlainUser;
use Vattra\Rbac\Tests\TestCase;

class CommandsTest extends TestCase
{
    public function testInstallCreatesRolesPermissionsAndGroups(): void
    {
        $this->installRbac();

        $this->assertEqualsCanonicalizing(
            ['super_admin', 'admin', 'editor', 'user'],
            Role::pluck('name')->all()
        );
        $this->assertSame('posts', Permission::where('name', 'view_posts')->value('group'));
        $this->assertTrue((bool) Role::where('name', 'super_admin')->value('is_locked'));

        $all = Permission::count();
        $super = Role::where('name', 'super_admin')->first();
        $admin = Role::where('name', 'admin')->first();
        $this->assertSame($all, $super->permissions()->count());
        $this->assertFalse($admin->permissions()->where('name', 'assign_permissions')->exists());
    }

    public function testGroupUsesTheLastSegment(): void
    {
        config(['rbac.roles.editor.permissions' => ['view_blog_posts']]);

        $this->installRbac();

        $this->assertSame('posts', Permission::where('name', 'view_blog_posts')->value('group'));
    }

    public function testInstallAndSyncAreIdempotent(): void
    {
        $this->installRbac();
        $count = Permission::count();

        $this->artisan('rbac:sync-permissions')->assertSuccessful();
        $this->artisan('rbac:seed')->assertSuccessful();

        $this->assertSame($count, Permission::count());
        $this->assertSame(4, Role::count());
    }

    public function testSyncPicksUpNewConfigPermissions(): void
    {
        $this->installRbac();

        config(['rbac.roles.editor.permissions' => ['view_posts', 'publish_posts']]);
        $this->artisan('rbac:sync-permissions')->assertSuccessful();

        $editor = Role::where('name', 'editor')->first();
        $this->assertEqualsCanonicalizing(
            ['view_posts', 'publish_posts'],
            $editor->permissions()->pluck('name')->all()
        );
    }

    public function testRbacMigrateCommandIsGone(): void
    {
        $this->assertArrayNotHasKey('rbac:migrate', Artisan::all());
    }

    public function testCheckSucceedsOnAHealthySetup(): void
    {
        $this->installRbac();

        $this->artisan('rbac:check')->assertSuccessful();
    }

    public function testCheckFailsWhenUserModelLacksTheTrait(): void
    {
        config(['rbac.user_model' => PlainUser::class]);

        $this->artisan('rbac:check')->assertFailed();
    }

    public function testCheckFailsWhenUserModelDoesNotExist(): void
    {
        config(['rbac.user_model' => 'App\\Nope\\Missing']);

        $this->artisan('rbac:check')->assertFailed();
    }

    public function testAssignRoleByIdAndEmail(): void
    {
        $this->installRbac();
        $byId = $this->makeUser();
        $byEmail = $this->makeUser();

        $this->artisan('rbac:assign-role', ['user' => (string) $byId->id, 'role' => 'editor'])
            ->assertSuccessful();
        $this->artisan('rbac:assign-role', ['user' => $byEmail->email, 'role' => 'user'])
            ->assertSuccessful();

        $this->assertTrue($byId->fresh()->hasRole('editor'));
        $this->assertTrue($byEmail->fresh()->hasRole('user'));
    }

    public function testAssignRoleSyncReplacesRoles(): void
    {
        $this->installRbac();
        $user = $this->makeUser('editor', 'user');

        $this->artisan('rbac:assign-role', ['user' => (string) $user->id, 'role' => 'admin', '--sync' => true])
            ->assertSuccessful();

        $this->assertSame(['admin'], $user->fresh()->getRoleNames()->all());
    }

    public function testAssignRoleFailsForUnknownRoleOrUser(): void
    {
        $this->installRbac();
        $user = $this->makeUser();

        $this->artisan('rbac:assign-role', ['user' => (string) $user->id, 'role' => 'ghost'])->assertFailed();
        $this->artisan('rbac:assign-role', ['user' => '9999', 'role' => 'editor'])->assertFailed();
    }

    public function testAssignRoleUsesTheConfiguredUserModel(): void
    {
        $this->installRbac();
        config(['rbac.user_model' => PlainUser::class, 'auth.providers.users.model' => PlainUser::class]);

        $this->artisan('rbac:assign-role', ['user' => '1', 'role' => 'editor'])->assertFailed();
    }
}
