<?php
// src/Services/PermissionRegistrar.php
namespace Vattra\Rbac\Services;

use Illuminate\Support\Facades\Gate;

class PermissionRegistrar
{
    public function register(): void
    {
        // 1. Super Admin bypasses all checks
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'hasRole')
                && $user->hasRole(config('rbac.super_admin_role', 'super_admin'))) {
                return true;
            }

            return method_exists($user, 'hasPermissionTo')
                && $user->hasPermissionTo($ability)
                ? true
                : null;
        });

        Gate::define('is_admin', fn ($u) => method_exists($u, 'hasRole') && $u->hasRole('super_admin', 'admin'));
        Gate::define('is_staff', fn ($u) => method_exists($u, 'hasRoleType') && $u->hasRoleType('system', 'admin', 'staff'));
        Gate::define('is_sales_rep', fn ($u) => method_exists($u, 'hasRole') && $u->hasRole('sales_rep'));
        Gate::define('is_inventory_manager', fn ($u) => method_exists($u, 'hasRole') && $u->hasRole('inventory_manager'));
        Gate::define('is_project_manager', fn ($u) => method_exists($u, 'hasRole') && $u->hasRole('project_manager'));
        Gate::define('is_finance_officer', fn ($u) => method_exists($u, 'hasRole') && $u->hasRole('finance_officer'));
        Gate::define('is_client', fn ($u) => method_exists($u, 'hasRole') && $u->hasRole('client'));
    }
}