<?php

declare(strict_types=1);

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:sync-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync configured permissions and role assignments into the database.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Syncing configured permissions and roles...');

        return $this->call('rbac:install');
    }
}
