<?php

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateCommand extends Command
{
    protected $signature = 'rbac:migrate 
                            {--fresh : Drop all tables and re-run migrations (DANGER)} 
                            {--seed : Seed permissions and roles after migrating}';

    protected $description = 'Run the RBAC migrations (and optionally seed) without touching other app tables';

    public function handle(): int
    {
        $this->info('📦 Running RBAC migrations...');

        // 1. Migrate ONLY the RBAC tables
        // We use --path to point to the package's migration folder
        $migrateOptions = [
            '--path' => 'vendor/vattra/laravel-rbac/src/Database/Migrations',
            '--force' => true,
        ];

        if ($this->option('fresh')) {
            if ($this->confirm('⚠️  This will drop the RBAC tables and re-create them. Continue?')) {
                Artisan::call('migrate:fresh', array_merge($migrateOptions, ['--path' => 'vendor/vattra/laravel-rbac/src/Database/Migrations']));
                $this->warn('RBAC tables dropped and re-created.');
            } else {
                $this->warn('Migration cancelled.');
                return self::FAILURE;
            }
        } else {
            Artisan::call('migrate', $migrateOptions);
            $this->line(Artisan::output());
        }

        // 2. Seed if requested
        if ($this->option('seed')) {
            $this->info('🌱 Seeding permissions and roles...');
            $this->call('rbac:seed');
        }

        $this->info('✅ RBAC migration complete.');
        return self::SUCCESS;
    }
}