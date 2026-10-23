<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Vattra\Rbac\Tests\Fixtures\Post;
use Vattra\Rbac\Tests\Fixtures\PostPolicy;
use Vattra\Rbac\Tests\TestCase;

class GateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->installRbac();
    }

    public function testGuestDoesNotCrashAndIsDenied(): void
    {
        $this->assertFalse(Gate::allows('view_posts'));
        $this->assertFalse(Gate::allows('is_admin'));
        $this->assertFalse(Gate::allows('view_posts', new Post()));
    }

    public function testPermissionNamesWorkAsAbilities(): void
    {
        $user = $this->makeUser('editor');

        $this->assertTrue(Gate::forUser($user)->allows('view_posts'));
        $this->assertFalse(Gate::forUser($user)->allows('assign_permissions'));
        $this->assertFalse(Gate::forUser($user)->allows('unknown_ability'));
    }

    public function testSuperAdminPassesWhenNoPolicyApplies(): void
    {
        $super = $this->makeUser('super_admin');

        $this->assertTrue(Gate::forUser($super)->allows('anything'));
    }

    public function testPoliciesAreNotBypassedByPermissions(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $editor = $this->makeUser('editor');

        // Without a model: permission grants it.
        $this->assertTrue(Gate::forUser($editor)->allows('view_posts'));
        // With a model that has a policy: the policy decides (no such method => deny).
        $this->assertFalse(Gate::forUser($editor)->allows('view_posts', new Post()));
        // The policy's own method is authoritative.
        $this->assertFalse(Gate::forUser($editor)->allows('view', new Post()));
    }

    public function testRbacPolicyBeforeLetsSuperAdminThroughPolicies(): void
    {
        Gate::policy(Post::class, PostPolicy::class);

        $super = $this->makeUser('super_admin');

        $this->assertTrue(Gate::forUser($super)->allows('view', new Post()));
    }

    public function testRoleGatesAreGeneratedFromConfig(): void
    {
        $this->assertTrue(Gate::forUser($this->makeUser('editor'))->allows('is_editor'));
        $this->assertFalse(Gate::forUser($this->makeUser('user'))->allows('is_editor'));
        $this->assertTrue(Gate::forUser($this->makeUser('admin'))->allows('is_admin'));
        $this->assertTrue(Gate::forUser($this->makeUser('super_admin'))->allows('is_admin'));
        $this->assertFalse(Gate::forUser($this->makeUser('editor'))->allows('is_admin'));
        $this->assertTrue(Gate::forUser($this->makeUser('editor'))->allows('is_staff'));
        $this->assertFalse(Gate::has('is_sales_rep'));
    }
}
