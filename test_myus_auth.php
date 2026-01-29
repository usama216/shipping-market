<?php

/**
 * MyUS Authentication Test Script
 * 
 * Tests different authentication methods to find the correct way to authenticate with MyUS API.
 * 
 * Usage: php test_myus_auth.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "🔐 MyUS Authentication Method Discovery\n";
echo str_repeat("=", 80) . "\n\n";

$config = config('carriers.myus');
$baseUrl = $config['base_url'] ?? 'https://gateway.myus.com';
$bearerToken = $config['bearer_token'] ?? '';
$apiKey = $config['api_key'] ?? '';
$affiliateId = $config['affiliate_id'] ?? '';
$memberId = $config['member_id'] ?? '';
$suite = $config['suite'] ?? '';
$password = $config['password'] ?? '';

// Clean bearer token (remove "Bearer " prefix if present)
$cleanBearerToken = str_replace('Bearer ', '', $bearerToken);

echo "📋 Configuration:\n";
echo "   Base URL: {$baseUrl}\n";
echo "   API Key: " . (strlen($apiKey) > 0 ? '***' . substr($apiKey, -4) : 'N/A') . "\n";
echo "   Bearer Token: " . (strlen($cleanBearerToken) > 0 ? '***' . substr($cleanBearerToken, -4) : 'N/A') . "\n";
echo "   Affiliate ID: {$affiliateId}\n";
echo "   Member ID: {$memberId}\n";
echo "   Suite: {$suite}\n\n";

// Test endpoint (try root first)
$testEndpoint = '/';

echo "🧪 Testing different authentication methods on: {$baseUrl}{$testEndpoint}\n\n";

// Method 1: Bearer token only (standard)
echo "1️⃣  Testing: Bearer Token Only\n";
try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$cleanBearerToken}",
        'Accept' => 'application/json',
    ])->get($baseUrl . $testEndpoint);
    
    echo "   Status: {$response->status()}\n";
    echo "   Response: " . substr($response->body(), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 2: Bearer token with API key in header
echo "2️⃣  Testing: Bearer Token + API Key Header\n";
try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$cleanBearerToken}",
        'X-API-Key' => $apiKey,
        'Accept' => 'application/json',
    ])->get($baseUrl . $testEndpoint);
    
    echo "   Status: {$response->status()}\n";
    echo "   Response: " . substr($response->body(), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 3: API Key only in header
echo "3️⃣  Testing: API Key Only (X-API-Key header)\n";
try {
    $response = Http::withHeaders([
        'X-API-Key' => $apiKey,
        'Accept' => 'application/json',
    ])->get($baseUrl . $testEndpoint);
    
    echo "   Status: {$response->status()}\n";
    echo "   Response: " . substr($response->body(), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 4: API Key in query string
echo "4️⃣  Testing: API Key in Query String\n";
try {
    $url = $baseUrl . $testEndpoint . '?api_key=' . urlencode($apiKey);
    $response = Http::withHeaders([
        'Accept' => 'application/json',
    ])->get($url);
    
    echo "   Status: {$response->status()}\n";
    echo "   Response: " . substr($response->body(), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 5: Basic Auth with API Key
echo "5️⃣  Testing: Basic Auth (API Key as username)\n";
try {
    $response = Http::withBasicAuth($apiKey, '')
        ->withHeaders([
            'Accept' => 'application/json',
        ])->get($baseUrl . $testEndpoint);
    
    echo "   Status: {$response->status()}\n";
    echo "   Response: " . substr($response->body(), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 6: Bearer + Affiliate ID
echo "6️⃣  Testing: Bearer Token + Affiliate ID Header\n";
try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$cleanBearerToken}",
        'X-Affiliate-ID' => $affiliateId,
        'Accept' => 'application/json',
    ])->get($baseUrl . $testEndpoint);
    
    echo "   Status: {$response->status()}\n";
    echo "   Response: " . substr($response->body(), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 7: All headers combined
echo "7️⃣  Testing: All Headers Combined\n";
try {
    $response = Http::withHeaders([
        'Authorization' => "Bearer {$cleanBearerToken}",
        'X-API-Key' => $apiKey,
        'X-Affiliate-ID' => $affiliateId,
        'X-Member-ID' => $memberId,
        'Accept' => 'application/json',
    ])->get($baseUrl . $testEndpoint);
    
    echo "   Status: {$response->status()}\n";
    echo "   Response: " . substr($response->body(), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 8: Try to get a new token using API key
echo "8️⃣  Testing: Token Refresh/Get New Token\n";
try {
    // Common token endpoints
    $tokenEndpoints = [
        '/oauth/token',
        '/api/oauth/token',
        '/api/v1/auth/token',
        '/auth/token',
        '/api/token',
    ];
    
    foreach ($tokenEndpoints as $tokenEndpoint) {
        try {
            $response = Http::asForm()->post($baseUrl . $tokenEndpoint, [
                'grant_type' => 'client_credentials',
                'client_id' => $apiKey,
                'client_secret' => $apiKey, // Some APIs use same value
            ]);
            
            if ($response->status() < 400) {
                echo "   ✅ Found token endpoint: {$tokenEndpoint}\n";
                echo "   Response: " . substr($response->body(), 0, 300) . "\n";
                break;
            }
        } catch (\Exception $e) {
            // Continue to next endpoint
        }
    }
} catch (\Exception $e) {
    echo "   Error: {$e->getMessage()}\n";
}
echo "\n";

// Method 9: Check if Bearer token needs to be refreshed
echo "9️⃣  Testing: Bearer Token Format Variations\n";
$tokenVariations = [
    $cleanBearerToken,
    "Bearer {$cleanBearerToken}",
    trim($cleanBearerToken),
];

foreach ($tokenVariations as $index => $token) {
    try {
        $response = Http::withHeaders([
            'Authorization' => $token,
            'Accept' => 'application/json',
        ])->get($baseUrl . $testEndpoint);
        
        echo "   Variation " . ($index + 1) . " (Status: {$response->status()})\n";
    } catch (\Exception $e) {
        // Skip
    }
}
echo "\n";

echo str_repeat("=", 80) . "\n";
echo "📊 Analysis:\n\n";

echo "💡 If all methods return 403, it likely means:\n";
echo "   1. The Bearer token has expired and needs to be refreshed\n";
echo "   2. The API key or credentials are incorrect\n";
echo "   3. Your IP address needs to be whitelisted\n";
echo "   4. The account doesn't have API access enabled\n";
echo "   5. The credentials are for a different environment (test vs production)\n\n";

echo "🔧 Next Steps:\n";
echo "   1. Contact MyUS support to:\n";
echo "      - Verify your API credentials are active\n";
echo "      - Get a fresh Bearer token\n";
echo "      - Confirm API access is enabled for your account\n";
echo "      - Get the correct API endpoint documentation\n";
echo "   2. Check if you need to whitelist your IP address\n";
echo "   3. Verify you're using production credentials (not test)\n";
echo "   4. Ask for example API calls with your credentials\n\n";
