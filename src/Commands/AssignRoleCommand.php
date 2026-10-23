<?php

declare(strict_types=1);

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;
use Vattra\Rbac\Models\Role;
use Vattra\Rbac\RbacManager;

class AssignRoleCommand extends Command
{
    protected $signature = 'rbac:assign-role
                            {user : User key (ID/UUID) or email address}
                            {role : Role name (e.g. admin, editor)}
                            {--sync : Replace all existing roles instead of appending}';

    protected $description = 'Assign an RBAC role to a user by key or email.';

    public function handle(RbacManager $manager): int
    {
        $userInput = (string) $this->argument('user');
        $roleName = (string) $this->argument('role');

        // 1. Resolve the role
        $role = $manager->findRole($roleName);

        if ($role === null) {
            $this->error("Role [{$roleName}] does not exist.");
            $available = Role::pluck('name')->implode(', ');

            if ($available !== '') {
                $this->line("   Available roles: {$available}");
            }

            return self::FAILURE;
        }

        // 2. Resolve the user: an email when it contains "@", otherwise the key
        $userModel = $manager->userModel();

        if (! class_exists($userModel) || ! method_exists($userModel, 'assignRole')) {
            $this->error("[{$userModel}] must use the HasRolesAndPermissions trait.");

            return self::FAILURE;
        }

        $user = str_contains($userInput, '@')
            ? $userModel::query()->where('email', $userInput)->first()
            : $userModel::query()->whereKey($userInput)->first();

        if ($user === null) {
            $this->error("User [{$userInput}] not found.");

            return self::FAILURE;
        }

        $superAdmin = RbacManager::superAdminRole();

        // 3. Guard: don't strip super admin silently
        if ($this->option('sync') && $user->hasRole($superAdmin) && $role->name !== $superAdmin) {
            if (! $this->confirm("User has '{$superAdmin}'. Syncing to '{$role->name}' will REMOVE it. Continue?")) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        // 4. Assign
        if ($this->option('sync')) {
            $user->syncRoles([$role->name]);
            $this->info("Synced roles: user #{$user->getKey()} now has ONLY [{$role->name}].");
        } else {
            $user->assignRole($role->name);
            $this->info("Assigned role [{$role->name}] to user #{$user->getKey()}.");
        }

        $this->line('   Current roles: ' . $user->getRoleNames()->implode(', '));

        return self::SUCCESS;
    }
}
