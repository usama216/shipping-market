<?php

/**
 * MyUS Connection Test Script
 * 
 * This script tests the MyUS API connection using the configured credentials.
 * 
 * Usage: php test_myus_connection.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔌 Testing MyUS API Connection...\n\n";

try {
    // Load MyUS carrier
    $carrier = \App\Carriers\CarrierFactory::make('myus');
    
    echo "✅ Carrier instance created\n";
    
    // Test authentication
    echo "🔐 Testing authentication...\n";
    $authenticated = $carrier->authenticate();
    
    if ($authenticated) {
        echo "✅ MyUS authentication successful!\n\n";
        
        // Display configuration
        $config = config('carriers.myus');
        echo "📋 Configuration:\n";
        echo "   Base URL: " . ($config['base_url'] ?? 'N/A') . "\n";
        echo "   API Key: " . (isset($config['api_key']) && !empty($config['api_key']) ? '***' . substr($config['api_key'], -4) : 'N/A') . "\n";
        echo "   Bearer Token: " . (isset($config['bearer_token']) && !empty($config['bearer_token']) ? '***' . substr($config['bearer_token'], -4) : 'N/A') . "\n";
        echo "   Affiliate ID: " . ($config['affiliate_id'] ?? 'N/A') . "\n";
        echo "   Member ID: " . ($config['member_id'] ?? 'N/A') . "\n";
        echo "   Suite: " . ($config['suite'] ?? 'N/A') . "\n";
        echo "\n";
        
        echo "✅ MyUS integration is ready to use!\n";
        echo "\n";
        echo "⚠️  Note: API endpoints need to be updated based on MyUS API documentation.\n";
        echo "   Current endpoints are placeholders and may need adjustment.\n";
        
    } else {
        echo "❌ MyUS authentication failed!\n";
        exit(1);
    }
    
} catch (\App\Carriers\Exceptions\CarrierAuthException $e) {
    echo "❌ Authentication Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "💡 Make sure you have set the following in your .env file:\n";
    echo "   MYUS_BASE_URL=https://gateway.myus.com\n";
    echo "   MYUS_API_KEY=your_api_key\n";
    echo "   MYUS_BEARER_TOKEN=your_bearer_token\n";
    echo "   MYUS_AFFILIATE_ID=your_affiliate_id\n";
    exit(1);
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n✅ Test completed successfully!\n";
