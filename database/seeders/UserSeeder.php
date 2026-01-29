<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin - has all permissions
        $admin = User::firstOrCreate(
            ['email' => 'admin@marketz.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
                'type' => User::USER_TYPE_ADMIN,
                'is_active' => User::STATUS_ACTIVE,
            ]
        );
        if (!$admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }

        // Warehouse Staff
        $warehouse = User::firstOrCreate(
            ['email' => 'warehouse@marketz.com'],
            [
                'first_name' => 'Warehouse',
                'last_name' => 'Staff',
                'password' => Hash::make('password'),
                'type' => User::USER_TYPE_WAREHOUSE,
                'is_active' => User::STATUS_ACTIVE,
            ]
        );
        if (!$warehouse->hasRole('warehouse')) {
            $warehouse->assignRole('warehouse');
        }

        // Operator
        $operator = User::firstOrCreate(
            ['email' => 'operator@marketz.com'],
            [
                'first_name' => 'Operator',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'type' => User::USER_TYPE_OPERATOR,
                'is_active' => User::STATUS_ACTIVE,
            ]
        );
        if (!$operator->hasRole('operator')) {
            $operator->assignRole('operator');
        }

        // Support Staff
        $support = User::firstOrCreate(
            ['email' => 'support@marketz.com'],
            [
                'first_name' => 'Support',
                'last_name' => 'Staff',
                'password' => Hash::make('password'),
                'type' => User::USER_TYPE_SUPPORT,
                'is_active' => User::STATUS_ACTIVE,
            ]
        );
        if (!$support->hasRole('support')) {
            $support->assignRole('support');
        }

        // Sales Staff
        $sales = User::firstOrCreate(
            ['email' => 'sales@marketz.com'],
            [
                'first_name' => 'Sales',
                'last_name' => 'Rep',
                'password' => Hash::make('password'),
                'type' => User::USER_TYPE_SALES,
                'is_active' => User::STATUS_ACTIVE,
            ]
        );
        if (!$sales->hasRole('sales')) {
            $sales->assignRole('sales');
        }

        $this->command->info('✅ Created/verified 5 system users with roles assigned');
    }
}

