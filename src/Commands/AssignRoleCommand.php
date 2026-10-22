<?php

namespace Vattra\Rbac\Commands;

use Illuminate\Console\Command;
use Vattra\Rbac\Models\Role;

class AssignRoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rbac:assign-role
                            {user : User ID or email address}
                            {role : Role name (e.g. admin, sales_rep)}
                            {--sync : Replace all existing roles instead of appending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign an RBAC role to a user by ID or email.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userInput = $this->argument('user');
        $roleName  = $this->argument('role');

        // 1. Resolve the role
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            $this->error("❌ Role [{$roleName}] does not exist.");
            $available = Role::pluck('name')->implode(', ');
            if ($available) {
                $this->line("   Available roles: {$available}");
            }
            return self::FAILURE;
        }

        // 2. Resolve the user (by ID or email)
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $user = is_numeric($userInput)
            ? $userModel::find($userInput)
            : $userModel::where('email', $userInput)->first();

        if (! $user) {
            $this->error("❌ User [{$userInput}] not found.");
            return self::FAILURE;
        }

        // 3. Guard: don't strip super_admin silently
        if ($this->option('sync') && $user->hasRole('super_admin') && $role->name !== 'super_admin') {
            if (! $this->confirm("⚠️  User has 'super_admin'. Syncing to '{$role->name}' will REMOVE it. Continue?")) {
                $this->warn('Aborted.');
                return self::FAILURE;
            }
        }

        // 4. Assign
        if ($this->option('sync')) {
            $user->syncRoles([$role->name]);
            $this->info("✅ Synced roles: user #{$user->id} now has ONLY [{$role->name}].");
        } else {
            $user->assignRole($role->name);
            $this->info("✅ Assigned role [{$role->name}] to user #{$user->id} ({$user->email}).");
        }

        // 5. Show final role list
        $user->flushCache();
        $roles = $user->roles()->pluck('name')->implode(', ');
        $this->line("   Current roles: {$roles}");

        return self::SUCCESS;
    }
}