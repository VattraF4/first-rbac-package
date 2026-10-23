<?php

declare(strict_types=1);

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;

class SeedCommand extends Command
{
    protected $signature = 'rbac:seed';
    protected $description = 'Seed RBAC roles and permissions';

    public function handle(): int
    {
        return $this->call('rbac:install');
    }
}
