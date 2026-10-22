<?php

namespace Vattra\Rbac;

use Illuminate\Support\ServiceProvider;
use Vattra\Rbac\Commands\InstallCommand;
use Vattra\Rbac\Commands\SeedCommand;
use Vattra\Rbac\Commands\MigrateCommand;
use Vattra\Rbac\Commands\CheckSetupCommand;
use Vattra\Rbac\Commands\AssignRoleCommand;
use Vattra\Rbac\Commands\SyncPermissionsCommand;
use Vattra\Rbac\Services\PermissionRegistrar;
use Vattra\Rbac\Middleware\RoleMiddleware;
use Vattra\Rbac\Middleware\PermissionMiddleware;

class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rbac.php',
            'rbac'
        );

        // Bind the permission registrar singleton
        $this->app->singleton(PermissionRegistrar::class);
    }

    public function boot(): void
    {
        // ─── Publishables (user runs artisan vendor:publish in THEIR app) ───
        $this->publishes([
            __DIR__ . '/../config/rbac.php'      => config_path('rbac.php'),
            __DIR__ . '/../Database/Migrations'  => database_path('migrations'),
            __DIR__ . '/../Database/Seeders'     => database_path('seeders'),
        ], 'rbac');

        // ─── Load migrations from package directly (no publish required) ───
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // ─── Register gates (runs when app boots) ───
        $this->app->make(PermissionRegistrar::class)->register();

        // ─── Register middleware aliases in consuming app ───
        $router = $this->app['router'];
        $router->aliasMiddleware('role',       RoleMiddleware::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);

        // ─── Register artisan commands (available in consuming app) ───
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MigrateCommand::class,
                SeedCommand::class,
                AssignRoleCommand::class,
                SyncPermissionsCommand::class,
                CheckSetupCommand::class,
            ]);
        }
    }
}