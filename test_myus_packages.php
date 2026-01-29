<?php

/**
 * MyUS Packages Test Script
 * 
 * This script tests retrieving packages and shipments from MyUS API.
 * 
 * Usage: 
 *   php test_myus_packages.php
 *   php test_myus_packages.php --suite=5975-441
 *   php test_myus_packages.php --member-id=5975441
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Parse command line arguments
$options = getopt('', ['suite:', 'member-id:', 'status:', 'date-from:', 'date-to:']);

$suite = $options['suite'] ?? null;
$memberId = $options['member-id'] ?? null;
$status = $options['status'] ?? null;
$dateFrom = $options['date-from'] ?? null;
$dateTo = $options['date-to'] ?? null;

echo "📦 Testing MyUS Packages Retrieval...\n\n";

try {
    $carrier = \App\Carriers\CarrierFactory::make('myus');
    
    echo "✅ Carrier instance created\n";
    
    // Test authentication
    echo "🔐 Authenticating...\n";
    $carrier->authenticate();
    echo "✅ Authentication successful\n\n";
    
    // Build filters
    $filters = [];
    if ($status) {
        $filters['status'] = $status;
    }
    if ($dateFrom) {
        $filters['date_from'] = $dateFrom;
    }
    if ($dateTo) {
        $filters['date_to'] = $dateTo;
    }
    
    // Get packages
    echo "📦 Retrieving packages...\n";
    echo "   Suite: " . ($suite ?? 'default from config') . "\n";
    echo "   Member ID: " . ($memberId ?? 'default from config') . "\n";
    if (!empty($filters)) {
        echo "   Filters: " . json_encode($filters) . "\n";
    }
    echo "\n";
    
    $packages = $carrier->getPackages($suite, $memberId, $filters);
    
    echo "✅ Packages retrieved successfully!\n";
    echo "   Count: " . count($packages) . "\n\n";
    
    if (count($packages) > 0) {
        echo "📋 Package List:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($packages as $index => $package) {
            $packageId = $package['id'] ?? $package['package_id'] ?? 'N/A';
            $packageStatus = $package['status'] ?? 'N/A';
            $tracking = $package['tracking_number'] ?? $package['tracking'] ?? 'N/A';
            $weight = $package['weight'] ?? 'N/A';
            $dateReceived = $package['date_received'] ?? $package['received_date'] ?? 'N/A';
            
            echo sprintf(
                "%d. ID: %s | Status: %s | Tracking: %s | Weight: %s | Date: %s\n",
                $index + 1,
                $packageId,
                $packageStatus,
                $tracking,
                $weight,
                $dateReceived
            );
        }
        
        echo str_repeat("-", 80) . "\n";
        
        // Show first package details
        if (isset($packages[0])) {
            echo "\n📄 First Package Details:\n";
            echo json_encode($packages[0], JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "ℹ️  No packages found with the specified criteria.\n";
    }
    
    echo "\n";
    
    // Get shipments
    echo "🚚 Retrieving shipments...\n";
    $shipments = $carrier->getShipments($suite, $memberId, $filters);
    
    echo "✅ Shipments retrieved successfully!\n";
    echo "   Count: " . count($shipments) . "\n\n";
    
    if (count($shipments) > 0) {
        echo "📋 Shipment List:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($shipments as $index => $shipment) {
            $shipmentId = $shipment['id'] ?? $shipment['shipment_id'] ?? 'N/A';
            $shipmentStatus = $shipment['status'] ?? 'N/A';
            $tracking = $shipment['tracking_number'] ?? $shipment['tracking'] ?? 'N/A';
            
            echo sprintf(
                "%d. ID: %s | Status: %s | Tracking: %s\n",
                $index + 1,
                $shipmentId,
                $shipmentStatus,
                $tracking
            );
        }
        
        echo str_repeat("-", 80) . "\n";
    } else {
        echo "ℹ️  No shipments found with the specified criteria.\n";
    }
    
    echo "\n✅ Test completed successfully!\n";
    
} catch (\App\Carriers\Exceptions\CarrierAuthException $e) {
    echo "❌ Authentication Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "💡 Make sure you have set the MyUS credentials in your .env file.\n";
    exit(1);
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "\n";
    echo "💡 This might be because:\n";
    echo "   1. MyUS API endpoints need to be updated in MyUSCarrier.php\n";
    echo "   2. The API response format is different than expected\n";
    echo "   3. Check the logs: storage/logs/carrier.log\n";
    exit(1);
}
