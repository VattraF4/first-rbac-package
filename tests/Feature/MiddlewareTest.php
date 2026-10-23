<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Vattra\Rbac\Tests\TestCase;

class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->installRbac();

        Route::middleware('rbac.role:admin|editor')->get('/role', fn () => 'ok');
        Route::middleware('rbac.role:admin,editor')->get('/role-comma', fn () => 'ok');
        Route::middleware('rbac.permission:destroy_posts')->get('/perm', fn () => 'ok');
        Route::middleware('rbac.permission:destroy_posts|view_roles')->get('/perm-or', fn () => 'ok');
    }

    public function testRoleMiddlewareGuestGets401ForJson(): void
    {
        $this->getJson('/role')->assertUnauthorized();
    }

    public function testRoleMiddlewareGuestGets401WithoutLoginRoute(): void
    {
        $this->get('/role')->assertUnauthorized();
    }

    public function testRoleMiddlewareGuestIsRedirectedWhenLoginRouteExists(): void
    {
        Route::get('/login', fn () => 'login')->name('login');
        Route::getRoutes()->refreshNameLookups();

        $this->get('/role')->assertRedirect('/login');
    }

    public function testRoleMiddlewareForbidsAuthenticatedUserWithoutRole(): void
    {
        $this->actingAs($this->makeUser('user'))->get('/role')->assertForbidden();
    }

    public function testRoleMiddlewareAllowsMatchingRole(): void
    {
        $this->actingAs($this->makeUser('editor'))->get('/role')->assertOk();
        $this->actingAs($this->makeUser('editor'))->get('/role-comma')->assertOk();
    }

    public function testRoleMiddlewareLetsSuperAdminThrough(): void
    {
        $this->actingAs($this->makeUser('super_admin'))->get('/role')->assertOk();
    }

    public function testPermissionMiddlewareGuestUserAndSuperAdmin(): void
    {
        $this->getJson('/perm')->assertUnauthorized();
        $this->actingAs($this->makeUser('user'))->get('/perm')->assertForbidden();
        $this->actingAs($this->makeUser('editor'))->get('/perm')->assertOk();
        $this->actingAs($this->makeUser('super_admin'))->get('/perm')->assertOk();
    }

    public function testPermissionMiddlewareSupportsPipeSyntax(): void
    {
        $this->actingAs($this->makeUser('editor'))->get('/perm-or')->assertOk();
        $this->actingAs($this->makeUser('user'))->get('/perm-or')->assertForbidden();
    }

    public function testLegacyAliasesAreNotRegisteredByDefault(): void
    {
        $aliases = app('router')->getMiddleware();

        $this->assertArrayHasKey('rbac.role', $aliases);
        $this->assertArrayHasKey('rbac.permission', $aliases);
        $this->assertArrayNotHasKey('role', $aliases);
        $this->assertArrayNotHasKey('permission', $aliases);
    }
}
