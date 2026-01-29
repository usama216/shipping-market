<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * SystemUserMigrationSeeder
 * 
 * Assigns default roles to existing system users based on their type.
 */
class SystemUserMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Assigning roles to existing system users...');

        // Get all non-customer users (system users)
        $systemUsers = User::where('type', '!=', 2) // Not customers
            ->orWhereNull('type')
            ->get();

        $this->command->info("Found {$systemUsers->count()} system users");

        $roleMapping = [
            User::USER_TYPE_ADMIN => 'super-admin',
            User::USER_TYPE_WAREHOUSE => 'warehouse',
            User::USER_TYPE_OPERATOR => 'operator',
            User::USER_TYPE_SUPPORT => 'support',
            User::USER_TYPE_SALES => 'sales',
        ];

        $assigned = 0;

        foreach ($systemUsers as $user) {
            // Skip if user already has a role
            if ($user->roles->count() > 0) {
                continue;
            }

            $roleName = $roleMapping[$user->type] ?? 'operator';
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if ($role) {
                $user->assignRole($role);
                $assigned++;
                $this->command->info("  - Assigned '{$roleName}' to {$user->email}");
            }
        }

        $this->command->info("✅ Assigned roles to {$assigned} system users");
    }
}
