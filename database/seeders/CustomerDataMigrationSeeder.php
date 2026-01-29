<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Customer;

/**
 * CustomerDataMigrationSeeder
 * 
 * Migrates existing customer data from users table to customers table.
 * Also updates related tables to reference customer_id.
 */
class CustomerDataMigrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting customer data migration...');

        // Get all customers from users table (type = 2)
        $customers = DB::table('users')
            ->where('type', 2) // USER_TYPE_CUSTOMER
            ->get();

        $this->command->info("Found {$customers->count()} customers to migrate");

        $migrated = 0;
        $userIdToCustomerId = [];

        foreach ($customers as $user) {
            // Check if customer already exists (by email)
            $existingCustomer = DB::table('customers')
                ->where('email', $user->email)
                ->first();

            if ($existingCustomer) {
                $userIdToCustomerId[$user->id] = $existingCustomer->id;
                continue;
            }

            // Insert into customers table
            $customerId = DB::table('customers')->insertGetId([
                'avatar' => $user->avatar,
                'stripe_id' => $user->stripe_id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'user_name' => $user->user_name,
                'phone' => $user->phone,
                'password' => $user->password,
                'suite' => $user->suite,
                'country' => $user->country,
                'date_of_birth' => $user->date_of_birth,
                'tax_id' => $user->tax_id,
                'address' => $user->address,
                'zip_code' => $user->zip_code,
                'state' => $user->state,
                'city' => $user->city ?? null,
                'email_verified_at' => $user->email_verified_at,
                'is_active' => $user->is_active,
                'is_old' => $user->is_old,
                'loyalty_points' => $user->loyalty_points ?? 0,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => now(),
            ]);

            $userIdToCustomerId[$user->id] = $customerId;
            $migrated++;
        }

        $this->command->info("✅ Migrated {$migrated} customers to customers table");

        // Update related tables with customer_id
        $this->updateRelatedTables($userIdToCustomerId);

        $this->command->info('✅ Customer data migration completed!');
    }

    /**
     * Update related tables to reference customer_id
     */
    private function updateRelatedTables(array $userIdToCustomerId): void
    {
        $tables = [
            'packages',
            'ships',
            'transactions',
            'user_addresses',
            'user_cards',
            'shipping_preferences',
            'loyalty_transactions',
            'coupon_usages',
            'package_change_requests',
        ];

        foreach ($tables as $table) {
            $this->command->info("Updating {$table}...");

            foreach ($userIdToCustomerId as $userId => $customerId) {
                DB::table($table)
                    ->where('user_id', $userId)
                    ->update(['customer_id' => $customerId]);
            }
        }
    }
}
