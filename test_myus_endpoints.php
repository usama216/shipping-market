<?php

/**
 * MyUS Endpoint Discovery Script
 * 
 * This script tries different common API endpoint patterns to find the correct MyUS API structure.
 * 
 * Usage: php test_myus_endpoints.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "🔍 MyUS Endpoint Discovery Test\n";
echo str_repeat("=", 80) . "\n\n";

$config = config('carriers.myus');
$baseUrl = $config['base_url'] ?? 'https://gateway.myus.com';
$bearerToken = str_replace('Bearer ', '', $config['bearer_token'] ?? '');
$apiKey = $config['api_key'] ?? '';
$affiliateId = $config['affiliate_id'] ?? '';
$memberId = $config['member_id'] ?? '';
$suite = $config['suite'] ?? '';

echo "📋 Configuration:\n";
echo "   Base URL: {$baseUrl}\n";
echo "   API Key: " . (strlen($apiKey) > 0 ? '***' . substr($apiKey, -4) : 'N/A') . "\n";
echo "   Bearer Token: " . (strlen($bearerToken) > 0 ? '***' . substr($bearerToken, -4) : 'N/A') . "\n";
echo "   Affiliate ID: {$affiliateId}\n";
echo "   Member ID: {$memberId}\n";
echo "   Suite: {$suite}\n\n";

// Common endpoint patterns to try
$endpoints = [
    // Root/health check
    '/' => 'GET',
    '/api' => 'GET',
    '/api/v1' => 'GET',
    
    // Packages endpoints
    '/api/packages' => 'GET',
    '/api/v1/packages' => 'GET',
    "/api/members/{$memberId}/packages" => 'GET',
    "/api/v1/members/{$memberId}/packages" => 'GET',
    "/api/suite/{$suite}/packages" => 'GET',
    
    // Affiliate endpoints
    '/api/affiliate/packages' => 'GET',
    "/api/affiliate/{$affiliateId}/packages" => 'GET',
    
    // Alternative patterns
    '/packages' => 'GET',
    '/v1/packages' => 'GET',
    '/rest/packages' => 'GET',
];

// Different authentication methods to try
$authMethods = [
    'bearer_only' => [
        'Authorization' => "Bearer {$bearerToken}",
    ],
    'bearer_with_api_key' => [
        'Authorization' => "Bearer {$bearerToken}",
        'X-API-Key' => $apiKey,
    ],
    'bearer_with_affiliate' => [
        'Authorization' => "Bearer {$bearerToken}",
        'X-Affiliate-ID' => $affiliateId,
    ],
    'all_headers' => [
        'Authorization' => "Bearer {$bearerToken}",
        'X-API-Key' => $apiKey,
        'X-Affiliate-ID' => $affiliateId,
    ],
    'api_key_only' => [
        'X-API-Key' => $apiKey,
    ],
    'query_string_auth' => [
        // Will add api_key to query string instead
    ],
];

echo "🧪 Testing endpoints...\n\n";

$successCount = 0;
$testCount = 0;

foreach ($endpoints as $endpoint => $method) {
    foreach ($authMethods as $authName => $headers) {
        $testCount++;
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        // For query string auth, add api_key to URL
        if ($authName === 'query_string_auth' && !empty($apiKey)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'api_key=' . urlencode($apiKey);
            $headers = [];
        }
        
        $defaultHeaders = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        try {
            $response = Http::withHeaders($allHeaders)
                ->timeout(5)
                ->{strtolower($method)}($url);
            
            $status = $response->status();
            $body = $response->body();
            
            // Check if it's a successful response (2xx) or at least not 403
            if ($status < 400) {
                echo "✅ SUCCESS: {$method} {$endpoint} (Auth: {$authName})\n";
                echo "   Status: {$status}\n";
                echo "   Response preview: " . substr($body, 0, 200) . "\n";
                echo "\n";
                $successCount++;
            } elseif ($status === 403) {
                // 403 means endpoint exists but access denied - this is progress!
                echo "🔒 ACCESS DENIED (403): {$method} {$endpoint} (Auth: {$authName})\n";
                echo "   This endpoint exists but access is denied. Try different credentials.\n";
                echo "\n";
            } elseif ($status === 404) {
                // 404 means endpoint doesn't exist - skip silently
                // echo "❌ NOT FOUND (404): {$method} {$endpoint} (Auth: {$authName})\n";
            } else {
                echo "⚠️  Status {$status}: {$method} {$endpoint} (Auth: {$authName})\n";
                echo "   Response: " . substr($body, 0, 100) . "\n";
                echo "\n";
            }
            
        } catch (\Exception $e) {
            // Skip connection errors silently
            // echo "❌ Error: {$method} {$endpoint} - {$e->getMessage()}\n";
        }
    }
}

echo str_repeat("=", 80) . "\n";
echo "📊 Test Summary:\n";
echo "   Total tests: {$testCount}\n";
echo "   Successful responses: {$successCount}\n";
echo "\n";

if ($successCount === 0) {
    echo "❌ No successful endpoints found.\n";
    echo "\n";
    echo "💡 Recommendations:\n";
    echo "   1. Check MyUS API documentation for the correct endpoint structure\n";
    echo "   2. Verify your credentials are correct\n";
    echo "   3. Contact MyUS support for API endpoint information\n";
    echo "   4. Check if you need to whitelist your IP address\n";
    echo "   5. Verify the base URL is correct (currently: {$baseUrl})\n";
} else {
    echo "✅ Found {$successCount} endpoint(s) that responded successfully!\n";
    echo "   Update MyUSCarrier.php with the working endpoint(s).\n";
}
