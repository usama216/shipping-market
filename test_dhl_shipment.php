<?php

/**
 * DHL Shipment Test Script
 * 
 * This script tests DHL shipment creation and captures the full error response.
 * Run: php test_dhl_shipment.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Carriers\DHL\DHLCarrier;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\DTOs\Address;
use App\Carriers\DTOs\PackageDetail;
use App\Carriers\DTOs\CommodityDetail;
use App\Models\Package as PackageModel;
use App\Models\Ship;

echo "=== DHL Shipment Test ===\n\n";

try {
    // Get the latest ship with packages
    $ship = Ship::with(['packages.items'])->latest()->first();
    
    if (!$ship) {
        echo "❌ No ships found. Please create a shipment first.\n";
        exit(1);
    }
    
    echo "📦 Found Ship ID: {$ship->id}\n";
    echo "📦 Packages: " . $ship->packages->count() . "\n\n";
    
    // Get packages
    $packages = $ship->packages;
    if ($packages->isEmpty()) {
        echo "❌ No packages found in ship.\n";
        exit(1);
    }
    
    $firstPackage = $packages->first();
    
    // Build ShipmentRequest
    $shipmentRequest = new ShipmentRequest(
        senderName: $firstPackage->from ?? 'Test Sender',
        senderCompany: $firstPackage->warehouse->name ?? 'Test Company',
        senderPhone: $firstPackage->warehouse->phone ?? '+1234567890',
        senderEmail: $firstPackage->warehouse->email ?? 'test@example.com',
        senderAddress: new Address(
            street1: $firstPackage->warehouse->address_line_1 ?? '123 Test St',
            street2: $firstPackage->warehouse->address_line_2 ?? null,
            city: $firstPackage->warehouse->city ?? 'Miami',
            state: $firstPackage->warehouse->state ?? 'FL',
            postalCode: $firstPackage->warehouse->postal_code ?? '33122',
            countryCode: $firstPackage->warehouse->country ?? 'US',
        ),
        recipientName: $ship->customer->name ?? 'Test Recipient',
        recipientAddress: new Address(
            street1: $ship->customer->addresses()->first()->address_line_1 ?? '456 Test Ave',
            street2: $ship->customer->addresses()->first()->address_line_2 ?? null,
            city: $ship->customer->addresses()->first()->city ?? 'Kingston',
            state: $ship->customer->addresses()->first()->state ?? 'Kingston',
            postalCode: $ship->customer->addresses()->first()->postal_code ?? 'JMAAW01',
            countryCode: $ship->customer->addresses()->first()->country ?? 'JM',
        ),
        recipientPhone: $ship->customer->phone ?? '3051234567',
        recipientEmail: $ship->customer->email ?? 'recipient@example.com',
        serviceType: 'P',
        packages: $packages->map(function($pkg) {
            return new PackageDetail(
                weight: $pkg->weight ?? 1,
                weightUnit: $pkg->weight_unit ?? 'LB',
                length: $pkg->length ?? 10,
                width: $pkg->width ?? 10,
                height: $pkg->height ?? 10,
                dimensionUnit: $pkg->dimension_unit ?? 'IN',
            );
        })->toArray(),
        commodities: $firstPackage->items->map(function($item) {
            return new CommodityDetail(
                description: $item->title ?? 'Test Item',
                quantity: $item->quantity ?? 1,
                unitValue: $item->value_per_unit ?? 10,
                totalValue: $item->total_line_value ?? 10,
                weight: $item->weight_per_unit ?? 1,
                weightUnit: $item->weight_unit ?? 'LB',
                hsCode: $item->hs_code ?? null,
                countryOfOrigin: 'US',
            );
        })->toArray(),
        declaredValue: $firstPackage->total_value ?? 10,
        currency: 'USD',
        referenceNumber: (string)$ship->id,
        shipDate: now()->format('Y-m-d'),
        recipientTaxId: $ship->customer->tax_id ?? null,
    );
    
    echo "🚀 Creating DHL Carrier instance...\n";
    $carrier = new DHLCarrier();
    
    echo "📤 Sending shipment request to DHL...\n\n";
    
    // Get package models for invoice
    $packageModels = $packages->toArray();
    
    // Call createShipment
    $result = $carrier->createShipment($shipmentRequest, $packageModels);
    
    if ($result->success) {
        echo "✅ SUCCESS!\n";
        echo "📦 Tracking Number: " . $result->trackingNumber . "\n";
        echo "📄 Label URL: " . ($result->labelUrl ?? 'N/A') . "\n";
    } else {
        echo "❌ FAILED!\n";
        echo "📝 Error: " . $result->errorMessage . "\n";
        echo "📋 Errors: " . json_encode($result->errors, JSON_PRETTY_PRINT) . "\n";
        echo "📄 Raw Response: " . json_encode($result->rawResponse, JSON_PRETTY_PRINT) . "\n";
    }
    
    // Check for error files
    echo "\n=== Checking Error Files ===\n";
    $errorFiles = glob(storage_path('logs/dhl-error-*.json'));
    if (!empty($errorFiles)) {
        // Sort by modification time, newest first
        usort($errorFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestErrorFile = $errorFiles[0];
        echo "📁 Latest Error File: " . basename($latestErrorFile) . "\n";
        echo "📄 Content:\n";
        echo json_encode(json_decode(file_get_contents($latestErrorFile), true), JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ No error files found.\n";
    }
    
    // Check for response files - specifically for /shipments endpoint
    echo "\n=== Checking Response Files ===\n";
    $responseFiles = glob(storage_path('logs/dhl-response-*.json'));
    if (!empty($responseFiles)) {
        usort($responseFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Find the shipment response file
        $shipmentResponseFile = null;
        foreach ($responseFiles as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (isset($data['endpoint']) && $data['endpoint'] === '/shipments') {
                $shipmentResponseFile = $file;
                break;
            }
        }
        
        if ($shipmentResponseFile) {
            echo "📁 Shipment Response File: " . basename($shipmentResponseFile) . "\n";
            $responseData = json_decode(file_get_contents($shipmentResponseFile), true);
            
            if (isset($responseData['status']) && $responseData['status'] >= 400) {
                echo "❌ Error Response Detected!\n";
                echo "📄 Status: " . $responseData['status'] . "\n";
                echo "📄 Raw Body Length: " . ($responseData['raw_body_length'] ?? 0) . "\n";
                
                if (isset($responseData['raw_body_string'])) {
                    echo "📄 Raw Body:\n";
                    echo $responseData['raw_body_string'] . "\n\n";
                    
                    // Try to parse and show additionalDetails
                    $parsed = json_decode($responseData['raw_body_string'], true);
                    if (isset($parsed['additionalDetails'])) {
                        echo "📋 Additional Details:\n";
                        echo json_encode($parsed['additionalDetails'], JSON_PRETTY_PRINT) . "\n";
                    }
                }
                
                if (isset($responseData['response']['additionalDetails'])) {
                    echo "📋 Additional Details (from parsed):\n";
                    echo json_encode($responseData['response']['additionalDetails'], JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "✅ Success Response (Status: " . ($responseData['status'] ?? 'unknown') . ")\n";
            }
        } else {
            echo "❌ No shipment response file found. Checking all files...\n";
            foreach (array_slice($responseFiles, 0, 5) as $file) {
                $data = json_decode(file_get_contents($file), true);
                echo "  - " . basename($file) . " (endpoint: " . ($data['endpoint'] ?? 'unknown') . ", status: " . ($data['status'] ?? 'unknown') . ")\n";
            }
        }
    } else {
        echo "❌ No response files found.\n";
    }
    
    // Check carrier log
    echo "\n=== Checking Carrier Log ===\n";
    $logFile = storage_path('logs/carrier-' . date('Y-m-d') . '.log');
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        $lines = explode("\n", $logContent);
        $recentLines = array_slice($lines, -50); // Last 50 lines
        echo "📄 Last 50 lines of carrier log:\n";
        echo implode("\n", $recentLines) . "\n";
    } else {
        echo "❌ Carrier log file not found.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "📋 Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Test Complete ===\n";
