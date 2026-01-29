<?php

/**
 * UPS Tracking Test Script
 * 
 * This script allows you to test UPS tracking functionality
 * 
 * Usage:
 *   php test_ups_tracking.php <tracking_number>
 * 
 * Example:
 *   php test_ups_tracking.php 1Z999AA10123456784
 * 
 * Test Tracking Numbers (Sandbox):
 *   - 1Z999AA10123456784 (Valid test tracking number)
 *   - 1Z999AA10123456785 (Another test number)
 * 
 * Production Tracking Numbers:
 *   Use any valid UPS tracking number starting with 1Z
 */

require __DIR__ . '/vendor/autoload.php';

use App\Carriers\CarrierFactory;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get tracking number from command line
$trackingNumber = $argv[1] ?? null;

if (!$trackingNumber) {
    echo "❌ Error: Tracking number required\n";
    echo "\nUsage: php test_ups_tracking.php <tracking_number>\n";
    echo "\nExample: php test_ups_tracking.php 1Z999AA10123456784\n";
    echo "\nTest Tracking Numbers (Sandbox):\n";
    echo "  - 1Z999AA10123456784\n";
    echo "  - 1Z999AA10123456785\n";
    exit(1);
}

echo "🚀 Testing UPS Tracking\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Tracking Number: {$trackingNumber}\n";
echo "Environment: " . (config('carriers.ups.sandbox') ? 'SANDBOX' : 'PRODUCTION') . "\n";
echo "Base URL: " . config('carriers.ups.base_url') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    // Get UPS carrier instance
    $upsCarrier = CarrierFactory::create('ups');
    
    echo "📡 Fetching tracking information...\n\n";
    
    // Call track method
    $trackingResponse = $upsCarrier->track($trackingNumber);
    
    // Display results
    echo "✅ Tracking Successful!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Status: {$trackingResponse->status}\n";
    echo "Description: {$trackingResponse->statusDescription}\n";
    echo "Estimated Delivery: " . ($trackingResponse->estimatedDelivery ?? 'N/A') . "\n";
    echo "Actual Delivery: " . ($trackingResponse->actualDelivery ?? 'N/A') . "\n";
    echo "Signed By: " . ($trackingResponse->signedBy ?? 'N/A') . "\n";
    echo "Current Location: " . ($trackingResponse->currentLocation ?? 'N/A') . "\n";
    echo "Events Count: " . count($trackingResponse->events) . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    // Display events
    if (!empty($trackingResponse->events)) {
        echo "📦 Tracking Events:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($trackingResponse->events as $index => $event) {
            echo ($index + 1) . ". " . ($event['timestamp'] ?? 'N/A') . "\n";
            echo "   Status: " . ($event['status'] ?? 'N/A') . "\n";
            echo "   Description: " . ($event['description'] ?? 'N/A') . "\n";
            echo "   Location: " . ($event['location'] ?? 'N/A') . "\n";
            echo "\n";
        }
    } else {
        echo "⚠️  No tracking events found\n\n";
    }
    
    // Display raw response (first 500 chars)
    echo "📄 Raw Response (preview):\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $rawResponseJson = json_encode($trackingResponse->rawResponse, JSON_PRETTY_PRINT);
    echo substr($rawResponseJson, 0, 500) . "...\n";
    echo "\n";
    
    echo "✅ Test completed successfully!\n";
    echo "\n💡 Check storage/logs/carrier-*.log for detailed logs\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nError Details:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Class: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString() . "\n";
    echo "\n💡 Check storage/logs/carrier-*.log for detailed error logs\n";
    exit(1);
}
