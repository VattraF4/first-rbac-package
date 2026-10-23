<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Vattra\Rbac\Tests\Fixtures\UuidUser;
use Vattra\Rbac\Tests\TestCase;

/** Custom table names, custom user foreign key and UUID user keys. */
class ConfigurableSchemaTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('rbac.user_model', UuidUser::class);
        $app['config']->set('rbac.user_table', 'uuid_users');
        $app['config']->set('rbac.user_key_type', 'uuid');
        $app['config']->set('rbac.user_foreign_key', 'member_id');
        $app['config']->set('rbac.tables', [
            'roles' => 'acl_roles',
            'permissions' => 'acl_permissions',
            'role_permissions' => 'acl_role_permissions',
            'user_roles' => 'acl_user_roles',
            'user_permissions' => 'acl_user_permissions',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/uuid_migrations');
    }

    private function member(): UuidUser
    {
        return UuidUser::create(['name' => 'Uuid', 'email' => 'u@example.test', 'password' => 'x']);
    }

    public function testCustomTablesAndForeignKeyAreUsed(): void
    {
        $this->assertTrue(Schema::hasTable('acl_roles'));
        $this->assertTrue(Schema::hasTable('acl_user_roles'));
        $this->assertTrue(Schema::hasColumn('acl_user_roles', 'member_id'));
        $this->assertFalse(Schema::hasTable('roles'));
    }

    public function testUuidUsersCanHoldRolesAndPermissions(): void
    {
        $this->installRbac();
        $user = $this->member();

        $user->assignRole('editor');
        $user->givePermissionTo('assign_permissions');

        $fresh = UuidUser::find($user->getKey());
        $this->assertTrue($fresh->hasRole('editor'));
        $this->assertTrue($fresh->hasPermissionTo('assign_permissions'));
        $this->assertTrue($fresh->hasPermissionTo('view_posts'));
    }

    public function testAssignRoleCommandAcceptsAUuid(): void
    {
        $this->installRbac();
        $user = $this->member();

        $this->artisan('rbac:assign-role', ['user' => (string) $user->getKey(), 'role' => 'admin'])
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->hasRole('admin'));
    }

    public function testCheckCommandPassesWithCustomSchema(): void
    {
        $this->installRbac();

        $this->artisan('rbac:check')->assertSuccessful();
    }
}
