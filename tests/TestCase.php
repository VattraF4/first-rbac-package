<?php

declare(strict_types=1);

namespace Vattra\Rbac\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;
use Vattra\Rbac\Facades\Rbac;
use Vattra\Rbac\RbacServiceProvider;
use Vattra\Rbac\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [RbacServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Rbac' => Rbac::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('rbac.user_model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function installRbac(): void
    {
        $this->artisan('rbac:install')->assertSuccessful();
    }

    /** Create a user (after seeding roles) holding the given roles. */
    protected function makeUser(string ...$roles): User
    {
        static $counter = 0;
        $counter++;

        $user = User::create([
            'name' => "User {$counter}",
            'email' => "user{$counter}@example.test",
            'password' => 'secret',
        ]);

        if ($roles !== []) {
            $user->assignRole(...$roles);
        }

        return $user;
    }
}
