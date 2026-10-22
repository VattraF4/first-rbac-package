<?php

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;

class CheckSetupCommand extends Command
{
    protected $signature = 'rbac:check';
    protected $description = 'Check if the RBAC package is installed correctly';

    public function handle()
    {
        $this->info('🔍 Checking RBAC Setup...');

        // Check if tables exist
        if (!\Schema::hasTable('roles') || !\Schema::hasTable('permissions')) {
            $this->error('❌ Database tables are missing! Run: php artisan rbac:install --seed');
            return;
        }

        // Check if User model has the trait
        $userModel = config('auth.providers.users.model');
        $user = new $userModel;

        if (!method_exists($user, 'hasPermissionTo')) {
            $this->error("❌ The '{$userModel}' model is missing the HasRolesAndPermissions trait!");
            $this->line("   Add 'use Vattra\Rbac\Traits\HasRolesAndPermissions;' to your User model.");
        } else {
            $this->info('✅ User model has the trait.');
        }

        // Check if gates are registered
        if (\Gate::has('view_products') || \Gate::has('*')) {
            $this->info('✅ Gates are registered.');
        } else {
            $this->error('❌ Gates are missing. Check RbacServiceProvider.');
        }

        $this->info('🎉 Setup complete!');
    }
}