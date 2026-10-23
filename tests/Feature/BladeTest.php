<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Vattra\Rbac\Tests\TestCase;

class BladeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->installRbac();
    }

    private function render(string $template): string
    {
        return trim(Blade::render($template));
    }

    public function testGuestSeesTheElseBranch(): void
    {
        $this->assertSame('no', $this->render("@role('admin') yes @else no @endrole"));
        $this->assertSame('no', $this->render("@permission('view_posts') yes @else no @endpermission"));
    }

    public function testRoleDirectives(): void
    {
        $this->actingAs($this->makeUser('editor'));

        $this->assertSame('yes', $this->render("@role('editor') yes @else no @endrole"));
        $this->assertSame('yes', $this->render("@role('admin|editor') yes @else no @endrole"));
        $this->assertSame('no', $this->render("@role('admin') yes @else no @endrole"));
        $this->assertSame('yes', $this->render("@hasanyrole('admin', 'editor') yes @else no @endhasanyrole"));
    }

    public function testPermissionDirective(): void
    {
        $this->actingAs($this->makeUser('editor'));

        $this->assertSame('yes', $this->render("@permission('destroy_posts') yes @else no @endpermission"));
        $this->assertSame('no', $this->render("@permission('assign_permissions') yes @else no @endpermission"));
    }
}
