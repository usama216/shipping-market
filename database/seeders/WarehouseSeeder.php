<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Package;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create warehouse permissions
        $this->seedPermissions();

        // Create the main default warehouse
        $warehouse = Warehouse::firstOrCreate(
            ['code' => 'MAIN-US'],
            [
                'name' => 'Main Warehouse',
                'address' => '2601 E Oakland Park Blvd',
                'address_line_2' => 'Unit 480',
                'city' => 'Fort Lauderdale',
                'state' => 'FL',
                'zip' => '33306',
                'country' => 'USA',
                'phone_number' => '1-844-269-4279',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $this->command->info("Warehouse '{$warehouse->name}' created/exists with ID: {$warehouse->id}");

        // Assign this warehouse to all customers without a warehouse assignment
        $customersUpdated = Customer::whereNull('warehouse_id')
            ->update(['warehouse_id' => $warehouse->id]);

        if ($customersUpdated > 0) {
            $this->command->info("Assigned warehouse to {$customersUpdated} customers");
        }

        // Assign this warehouse to all packages without a warehouse assignment
        $packagesUpdated = Package::whereNull('warehouse_id')
            ->update(['warehouse_id' => $warehouse->id]);

        if ($packagesUpdated > 0) {
            $this->command->info("Assigned warehouse to {$packagesUpdated} packages");
        }
    }

    /**
     * Seed warehouse-related permissions.
     */
    private function seedPermissions(): void
    {
        $permissions = [
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.default.set',
            'warehouses.status.toggle',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $this->command->info('Warehouse permissions created');

        // Assign to super-admin role
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
            $this->command->info('Assigned warehouse permissions to super-admin role');
        }

        // Assign view permission to operator role
        $operator = Role::where('name', 'operator')->first();
        if ($operator) {
            $operator->givePermissionTo(['warehouses.view']);
            $this->command->info('Assigned view permission to operator role');
        }
    }
}
