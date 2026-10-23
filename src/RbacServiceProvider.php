<?php

declare(strict_types=1);

namespace Vattra\Rbac;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Vattra\Rbac\Commands\AssignRoleCommand;
use Vattra\Rbac\Commands\CheckSetupCommand;
use Vattra\Rbac\Commands\InstallCommand;
use Vattra\Rbac\Commands\SeedCommand;
use Vattra\Rbac\Commands\SyncPermissionsCommand;
use Vattra\Rbac\Middleware\PermissionMiddleware;
use Vattra\Rbac\Middleware\RoleMiddleware;
use Vattra\Rbac\Services\PermissionRegistrar;

class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rbac.php', 'rbac');

        $this->app->singleton(PermissionRegistrar::class);

        // Scoped: reset between requests under Octane / queue workers.
        $this->app->scoped(RbacManager::class, fn ($app) => new RbacManager(
            $app->make(PermissionRegistrar::class)
        ));
        $this->app->alias(RbacManager::class, 'rbac');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/rbac.php' => config_path('rbac.php'),
        ], 'rbac-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'rbac-migrations');

        // "rbac" publishes everything (kept from earlier versions)
        $this->publishes([
            __DIR__ . '/../config/rbac.php' => config_path('rbac.php'),
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'rbac');

        // Migrations load straight from the package; publishing is optional.
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->app->make(PermissionRegistrar::class)->register();

        $this->registerMiddleware();
        $this->registerBladeDirectives();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                SeedCommand::class,
                AssignRoleCommand::class,
                SyncPermissionsCommand::class,
                CheckSetupCommand::class,
            ]);
        }
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('rbac.role', RoleMiddleware::class);
        $router->aliasMiddleware('rbac.permission', PermissionMiddleware::class);

        if (config('rbac.legacy_middleware_aliases', false)) {
            $router->aliasMiddleware('role', RoleMiddleware::class);
            $router->aliasMiddleware('permission', PermissionMiddleware::class);
        }
    }

    /**
     * @role('admin|editor') ... @endrole
     * @hasanyrole('admin', 'editor') ... @endhasanyrole
     * @permission('view_posts') ... @endpermission
     */
    protected function registerBladeDirectives(): void
    {
        $roles = static function (string ...$roles): bool {
            $user = auth()->user();

            return $user !== null
                && method_exists($user, 'hasRole')
                && $user->hasRole(...RbacManager::parseList($roles));
        };

        Blade::if('role', $roles);
        Blade::if('hasanyrole', $roles);

        Blade::if('permission', static function (string ...$permissions): bool {
            $user = auth()->user();

            return $user !== null
                && method_exists($user, 'hasAnyPermission')
                && $user->hasAnyPermission(RbacManager::parseList($permissions));
        });
    }
}
