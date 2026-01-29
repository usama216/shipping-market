<?php

/**
 * Complete Flow Test Script
 * 
 * Tests the full flow:
 * 1. Create package with export compliance fields
 * 2. Create ship request
 * 3. Generate commercial invoice
 * 4. Submit to DHL API
 * 
 * Usage: php test_complete_flow.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Package;
use App\Models\PackageItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Ship;
use App\Models\Warehouse;
use App\Services\CommercialInvoiceService;
use App\Services\CheckoutService;
use App\Services\DTOs\CheckoutRequest;
use App\Models\CarrierService;
use Illuminate\Support\Facades\DB;

echo "=== Complete Flow Test: Package → Ship Request → Invoice → DHL API ===\n\n";

try {
    DB::beginTransaction();

    // 1. Get or create test customer
    $customer = Customer::where('email', 'testdhl@marketz.com')->first();
    if (!$customer) {
        echo "Creating test customer...\n";
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'testdhl@marketz.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'is_active' => 1,
        ]);
    }
    echo "✓ Customer: {$customer->email}\n";

    // 2. Get or create customer address
    $address = $customer->addresses()->where('country_code', 'JM')->first();
    if (!$address) {
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'address_name' => 'Home',
            'full_name' => 'John Doe',
            'address_line_1' => '123 Main Street',
            'city' => 'Kingston',
            'state' => 'Kingston',
            'postal_code' => 'JMAAW01',
            'country' => 'Jamaica',
            'country_code' => 'JM',
            'phone_number' => '18761234567',
        ]);
    }
    echo "✓ Address: {$address->city}, {$address->country_code}\n";

    // 3. Get default warehouse
    $warehouse = Warehouse::where('is_default', true)->first();
    if (!$warehouse) {
        $warehouse = Warehouse::first();
    }
    echo "✓ Warehouse: {$warehouse->name}\n";

    // 4. Create package with export compliance fields
    echo "\n--- Step 1: Creating Package with Export Compliance ---\n";
    $package = Package::create([
        'package_id' => 'TEST-' . time(),
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'from' => 'Amazon',
        'date_received' => now(),
        'store_tracking_id' => 'TEST-TRACK-' . time(),
        'total_value' => 150.00,
        'status' => 3, // Ready to Send
        // Export compliance fields
        'incoterm' => 'DAP',
        'invoice_signature_name' => 'Authorized Shipper',
        'exporter_id_license' => 'EAR99',
        'us_filing_type' => '30.37(a) - Under $2,500',
        'exporter_code' => 'EXPUS',
    ]);
    echo "✓ Package created: {$package->package_id}\n";

    // 5. Create package item with HS code and EEI code
    $item = PackageItem::create([
        'package_id' => $package->id,
        'title' => 'Test Product - Electronics',
        'description' => 'Test electronic device for DHL shipment',
        'hs_code' => '8471.30.01',
        'eei_code' => 'NOEEI30.37(a)',
        'material' => 'Plastic, Metal',
        'quantity' => 1,
        'value_per_unit' => 150.00,
        'weight_per_unit' => 5.0,
        'weight_unit' => 'lb',
        'total_line_value' => 150.00,
        'total_line_weight' => 5.0,
        'length' => 12.0,
        'width' => 10.0,
        'height' => 8.0,
        'dimension_unit' => 'in',
    ]);
    echo "✓ Package item created with HS code: {$item->hs_code}, EEI code: {$item->eei_code}\n";

    // 6. Create ship request
    echo "\n--- Step 2: Creating Ship Request ---\n";
    $ship = Ship::create([
        'customer_id' => $customer->id,
        'tracking_number' => rand(10000000, 99999999),
        'total_weight' => 5.0,
        'total_price' => 150.00,
        'customer_address_id' => $address->id,
        'declared_value' => 150.00,
        'declared_value_currency' => 'USD',
        'status' => 'paid',
        'invoice_status' => 'paid',
    ]);
    $ship->packages()->attach($package->id);
    echo "✓ Ship request created: {$ship->tracking_number}\n";

    // 7. Get DHL carrier service
    $dhlService = CarrierService::where('carrier_code', 'dhl')
        ->where('service_code', 'EXPRESS_WORLDWIDE')
        ->first();
    
    if (!$dhlService) {
        echo "⚠ Warning: DHL service not found in database, using default\n";
    } else {
        $ship->carrier_service_id = $dhlService->id;
        $ship->save();
        echo "✓ Carrier service set: DHL Express Worldwide\n";
    }

    // 8. Generate commercial invoice
    echo "\n--- Step 3: Generating Commercial Invoice ---\n";
    $invoiceService = app(CommercialInvoiceService::class);
    $invoicePath = $invoiceService->generateInvoice($ship);
    
    if ($invoicePath) {
        echo "✓ Commercial invoice generated: {$invoicePath}\n";
        
        // Verify PDF exists
        $fullPath = storage_path('app/public/' . $invoicePath);
        if (file_exists($fullPath)) {
            $fileSize = filesize($fullPath);
            echo "  File size: " . number_format($fileSize / 1024, 2) . " KB\n";
        }
    } else {
        echo "❌ Failed to generate invoice\n";
        DB::rollBack();
        exit(1);
    }

    // 9. Get Base64 invoice for API
    echo "\n--- Step 4: Getting Invoice Base64 for API ---\n";
    $invoiceBase64 = $invoiceService->getInvoiceBase64($ship);
    if ($invoiceBase64) {
        echo "✓ Invoice Base64 encoded: " . strlen($invoiceBase64) . " characters\n";
    } else {
        echo "⚠ Warning: Could not get Base64 invoice\n";
    }

    // 10. Test DHL API submission
    echo "\n--- Step 5: Testing DHL API Submission ---\n";
    $carrier = \App\Carriers\CarrierFactory::make('dhl');
    $carrier->authenticate();
    echo "✓ DHL carrier authenticated\n";

    // Build shipment request
    $shipmentRequest = \App\Carriers\DTOs\ShipmentRequest::fromShip($ship, [$package]);
    
    // Submit to DHL
    echo "Submitting to DHL API...\n";
    $response = $carrier->createShipment($shipmentRequest, [$package]);

    if ($response->success) {
        echo "\n✅ SUCCESS! Complete Flow Test Passed!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Package ID: {$package->package_id}\n";
        echo "Ship ID: {$ship->id}\n";
        echo "Tracking Number: {$response->trackingNumber}\n";
        echo "Invoice Path: {$invoicePath}\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        DB::commit();
        
        echo "\n✅ All steps completed successfully!\n";
        echo "The system:\n";
        echo "  1. ✓ Created package with export compliance fields\n";
        echo "  2. ✓ Created ship request\n";
        echo "  3. ✓ Generated commercial invoice PDF\n";
        echo "  4. ✓ Retrieved invoice as Base64\n";
        echo "  5. ✓ Submitted to DHL API successfully\n";
        echo "  6. ✓ Received tracking number: {$response->trackingNumber}\n";
        
        return $response->trackingNumber;
        
    } else {
        echo "\n❌ DHL API Submission Failed\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Error: {$response->errorMessage}\n";
        
        if (!empty($response->errors)) {
            echo "\nErrors:\n";
            foreach ($response->errors as $error) {
                echo "  - {$error}\n";
            }
        }
        
        DB::rollBack();
        exit(1);
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ EXCEPTION!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
