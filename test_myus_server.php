<?php

/**
 * MyUS Server-Side API Test
 * 
 * This script should be run on your production server to test MyUS API connectivity.
 * Many APIs require requests from whitelisted IPs or authorized domains.
 * 
 * Usage on server:
 *   php test_myus_server.php
 *   OR access via browser: https://yourdomain.com/test_myus_server.php
 */

// If accessed via browser, show HTML output
$isBrowser = !empty($_SERVER['HTTP_HOST']);

if ($isBrowser) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><title>MyUS API Server Test</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
    echo ".success{color:green;}.error{color:red;}.info{color:blue;}.section{margin:20px 0;padding:15px;background:white;border-radius:5px;}</style></head><body>";
    echo "<h1>🔍 MyUS API Server Test</h1>";
    echo "<div class='section'><strong>Server Information:</strong><br>";
    echo "Server IP: " . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . "<br>";
    echo "Remote IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "<br>";
    echo "Host: " . ($_SERVER['HTTP_HOST'] ?? 'N/A') . "<br>";
    echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "<br>";
    echo "</div>";
    echo "<pre>";
}

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "🔍 MyUS API Server-Side Test\n";
echo str_repeat("=", 80) . "\n\n";

// Get server information
$serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
$host = $_SERVER['HTTP_HOST'] ?? 'N/A';

echo "📋 Server Information:\n";
echo "   Server IP: {$serverIp}\n";
echo "   Remote IP: {$remoteIp}\n";
echo "   Host: {$host}\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Laravel Version: " . app()->version() . "\n\n";

$config = config('carriers.myus');
$baseUrl = $config['base_url'] ?? 'https://gateway.myus.com';
$bearerToken = str_replace('Bearer ', '', $config['bearer_token'] ?? '');
$apiKey = $config['api_key'] ?? '';
$affiliateId = $config['affiliate_id'] ?? '';
$memberId = $config['member_id'] ?? '';
$suite = $config['suite'] ?? '';

echo "📋 MyUS Configuration:\n";
echo "   Base URL: {$baseUrl}\n";
echo "   API Key: " . (strlen($apiKey) > 0 ? '***' . substr($apiKey, -4) : 'N/A') . "\n";
echo "   Bearer Token: " . (strlen($bearerToken) > 0 ? '***' . substr($bearerToken, -4) : 'N/A') . "\n";
echo "   Affiliate ID: {$affiliateId}\n";
echo "   Member ID: {$memberId}\n";
echo "   Suite: {$suite}\n\n";

// Test 1: Basic connectivity
echo "1️⃣  Testing Basic Connectivity\n";
echo str_repeat("-", 80) . "\n";

try {
    $response = Http::timeout(10)->get($baseUrl);
    echo "✅ Server can reach {$baseUrl}\n";
    echo "   Status: {$response->status()}\n";
} catch (\Exception $e) {
    echo "❌ Cannot reach {$baseUrl}\n";
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Test 2: Try MyUS carrier authentication
echo "2️⃣  Testing MyUS Carrier Authentication\n";
echo str_repeat("-", 80) . "\n";

try {
    $carrier = \App\Carriers\CarrierFactory::make('myus');
    $authenticated = $carrier->authenticate();
    
    if ($authenticated) {
        echo "✅ Authentication successful\n";
    } else {
        echo "❌ Authentication failed\n";
    }
} catch (\Exception $e) {
    echo "❌ Authentication error: {$e->getMessage()}\n";
}
echo "\n";

// Test 3: Try different endpoints with full authentication
echo "3️⃣  Testing API Endpoints\n";
echo str_repeat("-", 80) . "\n";

$endpoints = [
    '/',
    '/api',
    '/api/v1',
    '/api/packages',
    '/api/v1/packages',
    "/api/members/{$memberId}/packages",
    "/api/suite/{$suite}/packages",
    '/affiliate/api',
    "/affiliate/{$affiliateId}/packages",
];

$successCount = 0;
foreach ($endpoints as $endpoint) {
    try {
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$bearerToken}",
            'X-API-Key' => $apiKey,
            'X-Affiliate-ID' => $affiliateId,
            'Accept' => 'application/json',
            'User-Agent' => 'Marketsz-MyUS-Integration/1.0',
        ])->timeout(10)->get($url);
        
        $status = $response->status();
        $body = substr($response->body(), 0, 200);
        
        if ($status < 400) {
            echo "✅ SUCCESS: {$endpoint}\n";
            echo "   Status: {$status}\n";
            echo "   Response: {$body}\n\n";
            $successCount++;
        } elseif ($status === 401) {
            echo "🔑 AUTH REQUIRED (401): {$endpoint}\n";
            echo "   Endpoint exists but needs different authentication\n\n";
        } elseif ($status === 403) {
            echo "🔒 ACCESS DENIED (403): {$endpoint}\n";
            echo "   Endpoint exists but access denied\n";
            echo "   This might require IP whitelisting\n\n";
        } elseif ($status === 404) {
            // Skip 404s
        } else {
            echo "⚠️  Status {$status}: {$endpoint}\n";
            echo "   Response: {$body}\n\n";
        }
    } catch (\Exception $e) {
        // Skip connection errors
    }
}

// Test 4: Try to get packages
echo "4️⃣  Testing Package Retrieval\n";
echo str_repeat("-", 80) . "\n";

try {
    $carrier = \App\Carriers\CarrierFactory::make('myus');
    $packages = $carrier->getPackages($suite, $memberId);
    
    echo "✅ Package retrieval successful!\n";
    echo "   Packages found: " . count($packages) . "\n";
    
    if (count($packages) > 0) {
        echo "\n   First package:\n";
        echo "   " . json_encode($packages[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Package retrieval failed\n";
    echo "   Error: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}\n";
    echo "   Line: {$e->getLine()}\n";
}
echo "\n";

// Test 5: Try to get shipments
echo "5️⃣  Testing Shipment Retrieval\n";
echo str_repeat("-", 80) . "\n";

try {
    $carrier = \App\Carriers\CarrierFactory::make('myus');
    $shipments = $carrier->getShipments($suite, $memberId);
    
    echo "✅ Shipment retrieval successful!\n";
    echo "   Shipments found: " . count($shipments) . "\n";
} catch (\Exception $e) {
    echo "❌ Shipment retrieval failed\n";
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Summary
echo str_repeat("=", 80) . "\n";
echo "📊 Test Summary\n";
echo str_repeat("=", 80) . "\n";
echo "   Server IP: {$serverIp}\n";
echo "   Successful endpoints: {$successCount}\n\n";

if ($successCount > 0) {
    echo "✅ SUCCESS! Found {$successCount} working endpoint(s).\n";
    echo "   The API is accessible from this server.\n";
    echo "   Update MyUSCarrier.php with the working endpoints.\n";
} else {
    echo "❌ No working endpoints found.\n\n";
    echo "💡 Possible reasons:\n";
    echo "   1. IP whitelisting required - Provide this IP to MyUS: {$serverIp}\n";
    echo "   2. Bearer token expired - Contact MyUS for a new token\n";
    echo "   3. Wrong base URL - Contact MyUS for correct API URL\n";
    echo "   4. API access not enabled - Contact MyUS to enable API access\n\n";
    echo "🔧 Next Steps:\n";
    echo "   1. Contact MyUS support with:\n";
    echo "      - Server IP: {$serverIp}\n";
    echo "      - Domain: {$host}\n";
    echo "      - Affiliate ID: {$affiliateId}\n";
    echo "      - Request IP whitelisting\n";
    echo "   2. Ask for:\n";
    echo "      - Correct API base URL\n";
    echo "      - Fresh Bearer token\n";
    echo "      - Example API calls\n";
}

if ($isBrowser) {
    echo "</pre>";
    echo "<div class='section'><strong>Note:</strong> This test should be run on your production server where MyUS API access is configured.</div>";
    echo "</body></html>";
}
