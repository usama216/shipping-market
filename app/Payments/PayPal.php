<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * PayPal REST API v2 Client
 * 
 * Implements PayPal Orders API for one-time payments.
 * Uses OAuth 2.0 for authentication with token caching.
 * 
 * @see https://developer.paypal.com/docs/api/orders/v2/
 */
class PayPal
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');

        $mode = config('services.paypal.mode', 'sandbox');
        $this->baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Get OAuth 2.0 access token
     * 
     * Token is cached for 1 hour (PayPal tokens are valid for ~9 hours)
     * 
     * @return string Access token
     * @throws \Exception if token request fails
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'paypal_access_token_' . config('services.paypal.mode');

        return Cache::remember($cacheKey, 3600, function () {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->baseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ]);

            if (!$response->successful()) {
                Log::error('PayPal: Failed to get access token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to authenticate with PayPal');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Create a PayPal Order
     * 
     * @param array $data Order data with keys:
     *   - amount: float (USD)
     *   - description: string
     *   - return_url: string (where to redirect after approval)
     *   - cancel_url: string (where to redirect if cancelled)
     *   - reference_id: string (optional, for tracking)
     * 
     * @return array Order data including 'id' and 'approval_url'
     * @throws \Exception if order creation fails
     */
    public function createOrder(array $data): array
    {
        $token = $this->getAccessToken();

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $data['reference_id'] ?? uniqid('ship_'),
                    'description' => $data['description'] ?? 'Shipment Payment',
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($data['amount'], 2, '.', ''),
                    ],
                ]
            ],
            'payment_source' => [
                'paypal' => [
                    'experience_context' => [
                        'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                        'brand_name' => config('app.name'),
                        'locale' => 'en-US',
                        'landing_page' => 'LOGIN',
                        'user_action' => 'PAY_NOW',
                        'return_url' => $data['return_url'],
                        'cancel_url' => $data['cancel_url'],
                    ],
                ],
            ],
        ];

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", $payload);

        if (!$response->successful()) {
            Log::error('PayPal: Failed to create order', [
                'status' => $response->status(),
                'body' => $response->json(),
                'payload' => $payload,
            ]);
            throw new \Exception('Failed to create PayPal order: ' . ($response->json('message') ?? 'Unknown error'));
        }

        $order = $response->json();

        // Find the approval URL from the response links
        $approvalUrl = collect($order['links'] ?? [])
            ->firstWhere('rel', 'payer-action')['href'] ?? null;

        Log::info('PayPal: Order created', [
            'order_id' => $order['id'],
            'status' => $order['status'],
        ]);

        return [
            'id' => $order['id'],
            'status' => $order['status'],
            'approval_url' => $approvalUrl,
        ];
    }

    /**
     * Capture a PayPal Order after customer approval
     * 
     * @param string $orderId PayPal order ID
     * @return array Capture result with transaction details
     * @throws \Exception if capture fails
     */
    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->contentType('application/json')
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if (!$response->successful()) {
            Log::error('PayPal: Failed to capture order', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \Exception('Failed to capture PayPal payment: ' . ($response->json('message') ?? 'Unknown error'));
        }

        $capture = $response->json();

        Log::info('PayPal: Order captured', [
            'order_id' => $orderId,
            'status' => $capture['status'],
        ]);

        return [
            'id' => $capture['id'],
            'status' => $capture['status'],
            'payer' => $capture['payer'] ?? null,
            'purchase_units' => $capture['purchase_units'] ?? [],
        ];
    }

    /**
     * Get order details
     * 
     * @param string $orderId PayPal order ID
     * @return array Order details
     * @throws \Exception if retrieval fails
     */
    public function getOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

        if (!$response->successful()) {
            Log::error('PayPal: Failed to get order', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \Exception('Failed to retrieve PayPal order');
        }

        return $response->json();
    }
}
