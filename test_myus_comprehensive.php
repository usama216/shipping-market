<?php

/**
 * Comprehensive MyUS API Test
 * Tests various URL patterns, authentication methods, and endpoints
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

echo "🔍 Comprehensive MyUS API Discovery Test\n";
echo str_repeat("=", 80) . "\n\n";

$config = config('carriers.myus');
$bearerToken = str_replace('Bearer ', '', $config['bearer_token'] ?? '');
$apiKey = $config['api_key'] ?? '';
$affiliateId = $config['affiliate_id'] ?? '';
$memberId = $config['member_id'] ?? '';
$suite = $config['suite'] ?? '';

// Try different base URLs
$baseUrls = [
    'https://gateway.myus.com',
    'https://api.myus.com',
    'https://api.gateway.myus.com',
    'https://www.myus.com/api',
    'https://affiliate.myus.com/api',
    'https://partner.myus.com/api',
];

// Common endpoint patterns
$endpoints = [
    '',
    '/',
    '/api',
    '/api/v1',
    '/v1',
    '/rest',
    '/rest/api',
    '/affiliate',
    '/affiliate/api',
    "/affiliate/{$affiliateId}",
    "/member/{$memberId}",
    "/suite/{$suite}",
];

$successCount = 0;
$testCount = 0;

foreach ($baseUrls as $baseUrl) {
    echo "🌐 Testing Base URL: {$baseUrl}\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($endpoints as $endpoint) {
        $testCount++;
        $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
        
        // Try with Bearer token
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$bearerToken}",
                'X-API-Key' => $apiKey,
                'X-Affiliate-ID' => $affiliateId,
                'Accept' => 'application/json',
            ])->timeout(5)->get($url);
            
            $status = $response->status();
            $body = substr($response->body(), 0, 300);
            
            if ($status < 400) {
                echo "✅ SUCCESS: {$url}\n";
                echo "   Status: {$status}\n";
                echo "   Response: {$body}\n\n";
                $successCount++;
            } elseif ($status === 401) {
                echo "🔑 AUTH REQUIRED (401): {$url}\n";
                echo "   This endpoint exists but needs authentication!\n\n";
            } elseif ($status === 404) {
                // Skip 404s silently
            } elseif ($status !== 403) {
                echo "⚠️  Status {$status}: {$url}\n";
                echo "   Response: {$body}\n\n";
            }
        } catch (\Exception $e) {
            // Skip connection errors
        }
    }
    echo "\n";
}

// Try POST to common authentication endpoints
echo "🔐 Testing Authentication Endpoints\n";
echo str_repeat("-", 80) . "\n";

$authEndpoints = [
    '/oauth/token',
    '/api/oauth/token',
    '/auth/token',
    '/api/auth/token',
    '/api/v1/auth/token',
    '/login',
    '/api/login',
    '/authenticate',
    '/api/authenticate',
];

foreach ($baseUrls as $baseUrl) {
    foreach ($authEndpoints as $authEndpoint) {
        try {
            $url = rtrim($baseUrl, '/') . $authEndpoint;
            
            // Try with API key
            $response = Http::asForm()->post($url, [
                'grant_type' => 'client_credentials',
                'client_id' => $apiKey,
                'client_secret' => $apiKey,
            ]);
            
            if ($response->status() < 400) {
                echo "✅ AUTH ENDPOINT FOUND: {$url}\n";
                echo "   Status: {$response->status()}\n";
                echo "   Response: " . substr($response->body(), 0, 300) . "\n\n";
                $successCount++;
            }
        } catch (\Exception $e) {
            // Skip
        }
    }
}

echo str_repeat("=", 80) . "\n";
echo "📊 Test Summary:\n";
echo "   Total tests: {$testCount}\n";
echo "   Successful responses: {$successCount}\n\n";

if ($successCount === 0) {
    echo "❌ No working endpoints found.\n\n";
    echo "💡 This strongly suggests:\n";
    echo "   1. The API base URL is incorrect\n";
    echo "   2. IP whitelisting is required\n";
    echo "   3. Credentials are expired/invalid\n";
    echo "   4. API access is not enabled for this account\n";
    echo "   5. The API might be behind a VPN or require specific network access\n\n";
    echo "🔧 Action Required:\n";
    echo "   Contact MyUS support immediately with:\n";
    echo "   - Your credentials (Affiliate ID: {$affiliateId})\n";
    echo "   - Request: 'What is the correct API base URL?'\n";
    echo "   - Request: 'Do I need IP whitelisting?'\n";
    echo "   - Request: 'Can you provide example API calls?'\n";
    echo "   - Request: 'Is my Bearer token still valid?'\n";
} else {
    echo "✅ Found {$successCount} working endpoint(s)!\n";
    echo "   Update MyUSCarrier.php with the working URL(s).\n";
}
