<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Warehouse;
use App\Helpers\PackageStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestCustomer extends Command
{
    protected $signature = 'test:create-customer';
    protected $description = 'Create a test customer with packages and address for DHL testing';

    public function handle()
    {
        $this->info('=== Creating Test Customer for DHL Testing ===');
        $this->newLine();

        // 1. Get warehouse
        $warehouse = Warehouse::getDefault() ?: Warehouse::first();
        if (!$warehouse) {
            $this->error('No warehouse found. Please create a warehouse first.');
            return 1;
        }
        $this->info("✓ Using warehouse: {$warehouse->name} (ID: {$warehouse->id})");

        // 2. Create customer
        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'testdhl@marketsz.com',
            'phone' => '3051234567',
            'password' => Hash::make('password123'),
            'suite' => Customer::generateSuiteNumber('JM', 'Jamaica'),
            'warehouse_id' => $warehouse->id,
            'country' => 'Jamaica',
            'state' => 'Kingston',
            'city' => 'Kingston',
            'zip_code' => 'JMAAW01',
            'address' => '123 Test Street',
            'date_of_birth' => '1990-01-15',
            'is_active' => true,
            'email_verified_at' => now(),
            'referral_code' => Customer::generateReferralCode(),
        ]);
        $this->info("✓ Created customer: {$customer->first_name} {$customer->last_name}");
        $this->info("  Email: {$customer->email}");
        $this->info("  Password: password123");
        $this->info("  Suite: {$customer->suite}");
        $this->newLine();

        // 3. Create address
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_name' => 'Home Address - Jamaica',
            'full_name' => 'John Doe',
            'address_line_1' => '123 Main Street',
            'city' => 'Kingston',
            'state' => 'Kingston',
            'postal_code' => 'JMAAW01',
            'country' => 'Jamaica',
            'country_code' => 'JM',
            'phone_number' => '3051234567',
            'is_default_us' => false,
            'is_default_uk' => false,
        ]);
        $this->info("✓ Created shipping address: {$address->address_name}");
        $this->info("  Address: {$address->address_line_1}, {$address->city}, {$address->country}");
        $this->newLine();

        // 4. Create packages
        $packages = [];

        // Package 1
        $pkg1 = Package::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'package_id' => 'PKG-' . strtoupper(uniqid()),
            'from' => 'Amazon',
            'date_received' => now()->subDays(5),
            'total_value' => 150.00,
            'weight_unit' => 'LB',
            'dimension_unit' => 'IN',
            'status' => PackageStatus::READY_TO_SEND,
            'note' => 'Test package for DHL integration',
        ]);
        PackageItem::create([
            'package_id' => $pkg1->id,
            'title' => 'Wireless Headphones',
            'description' => 'Bluetooth wireless headphones',
            'hs_code' => '8518.62.00',
            'quantity' => 1,
            'value_per_unit' => 150.00,
            'weight_per_unit' => 1.5,
            'weight_unit' => 'LB',
            'total_line_value' => 150.00,
            'total_line_weight' => 1.5,
            'length' => 8.0,
            'width' => 6.0,
            'height' => 4.0,
            'dimension_unit' => 'IN',
        ]);
        $packages[] = $pkg1;
        $this->info("✓ Created Package 1: {$pkg1->package_id} (1.5 lbs, \$150.00)");

        // Package 2
        $pkg2 = Package::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'package_id' => 'PKG-' . strtoupper(uniqid()),
            'from' => 'Nike Store',
            'date_received' => now()->subDays(3),
            'total_value' => 85.00,
            'weight_unit' => 'LB',
            'dimension_unit' => 'IN',
            'status' => PackageStatus::READY_TO_SEND,
            'note' => 'Test package for DHL integration',
        ]);
        PackageItem::create([
            'package_id' => $pkg2->id,
            'title' => 'Running Shoes',
            'description' => 'Men\'s athletic running shoes',
            'hs_code' => '6404.11.00',
            'quantity' => 1,
            'value_per_unit' => 85.00,
            'weight_per_unit' => 2.0,
            'weight_unit' => 'LB',
            'total_line_value' => 85.00,
            'total_line_weight' => 2.0,
            'length' => 12.0,
            'width' => 8.0,
            'height' => 6.0,
            'dimension_unit' => 'IN',
        ]);
        $packages[] = $pkg2;
        $this->info("✓ Created Package 2: {$pkg2->package_id} (2.0 lbs, \$85.00)");

        // Package 3
        $pkg3 = Package::create([
            'customer_id' => $customer->id,
            'warehouse_id' => $warehouse->id,
            'package_id' => 'PKG-' . strtoupper(uniqid()),
            'from' => 'Bookstore',
            'date_received' => now()->subDays(2),
            'total_value' => 45.00,
            'weight_unit' => 'LB',
            'dimension_unit' => 'IN',
            'status' => PackageStatus::READY_TO_SEND,
            'note' => 'Test package for DHL integration',
        ]);
        PackageItem::create([
            'package_id' => $pkg3->id,
            'title' => 'Programming Books',
            'description' => 'Set of programming books',
            'hs_code' => '4901.99.00',
            'quantity' => 3,
            'value_per_unit' => 15.00,
            'weight_per_unit' => 1.5,
            'weight_unit' => 'LB',
            'total_line_value' => 45.00,
            'total_line_weight' => 4.5,
            'length' => 10.0,
            'width' => 8.0,
            'height' => 5.0,
            'dimension_unit' => 'IN',
        ]);
        $packages[] = $pkg3;
        $this->info("✓ Created Package 3: {$pkg3->package_id} (4.5 lbs, \$45.00)");
        $this->newLine();

        // Summary
        $totalWeight = array_sum(array_map(fn($pkg) => $pkg->billed_weight, $packages));
        $totalValue = array_sum(array_map(fn($pkg) => $pkg->total_value, $packages));

        $this->info('=== Summary ===');
        $this->info("Customer ID: {$customer->id}");
        $this->info("Total Packages: " . count($packages));
        $this->info("Total Weight: {$totalWeight} lbs");
        $this->info("Total Value: \$" . number_format($totalValue, 2));
        $this->newLine();

        $this->info('=== Login Credentials ===');
        $this->info("Email: {$customer->email}");
        $this->info("Password: password123");
        $this->newLine();

        $this->info('=== Next Steps ===');
        $this->info('1. Login at: /customer/dashboard');
        $this->info('2. Go to "Ready to Send" packages');
        $this->info('3. Select packages and create shipment');
        $this->info("4. Select address: {$address->address_name}");
        $this->info('5. Get rates - DHL should appear!');
        $this->info('6. Complete checkout');
        $this->newLine();

        $this->info('=== Customer Details ===');
        $this->info("Customer ID: {$customer->id}");
        $this->info("Package IDs: " . implode(', ', array_map(fn($p) => $p->id, $packages)));
        $this->info("Address ID: {$address->id}");
        $this->newLine();

        $this->info('✅ Test customer created successfully!');
        return 0;
    }
}
