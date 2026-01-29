<?php

namespace App\Console\Commands;

use App\Carriers\CarrierFactory;
use App\Carriers\DTOs\Address;
use App\Carriers\DTOs\PackageDetail;
use App\Carriers\DTOs\CommodityDetail;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\Exceptions\CarrierException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Complete FedEx API Journey Test
 * 
 * Tests: Authentication → Rate Quote → Create Shipment → Track
 * 
 * Usage: php artisan fedex:test-journey
 */
class TestFedExJourney extends Command
{
    protected $signature = 'fedex:test-journey 
                            {--skip-ship : Skip shipment creation (rate only)}
                            {--track= : Track an existing tracking number}';

    protected $description = 'Test complete FedEx API journey: auth → rate → ship → track';

    private string $baseUrl;
    private string $token;
    private array $config;

    public function handle(): int
    {
        $this->config = config('carriers.fedex');
        $this->baseUrl = $this->config['base_url'] ?? 'https://apis-sandbox.fedex.com';

        $this->info("🚀 FedEx API Journey Test");
        $this->info("Base URL: {$this->baseUrl}\n");

        // If tracking only
        if ($trackingNumber = $this->option('track')) {
            return $this->testTracking($trackingNumber);
        }

        // Step 1: Authentication
        $this->info("Step 1: Authentication");
        if (!$this->authenticate()) {
            return Command::FAILURE;
        }
        $this->info("✅ Authentication successful!\n");

        // Step 2: Rate Quote
        $this->info("Step 2: Rate Quote");
        $rates = $this->getRates();
        if ($rates === null) {
            return Command::FAILURE;
        }

        if (empty($rates)) {
            $this->warn("⚠️ No rates returned (sandbox may have limitations)\n");
        } else {
            $this->info("✅ Rate quote successful! Found " . count($rates) . " service options\n");
            $this->displayRates($rates);
        }

        // Step 3: Create Shipment (unless skipped)
        if (!$this->option('skip-ship')) {
            $this->info("\nStep 3: Create Shipment");
            $shipment = $this->createShipment();

            if ($shipment) {
                $this->info("✅ Shipment created successfully!");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Tracking Number', $shipment['trackingNumber'] ?? 'N/A'],
                        ['Master Tracking', $shipment['masterTrackingNumber'] ?? 'N/A'],
                        ['Service', $shipment['serviceType'] ?? 'N/A'],
                    ]
                );

                // Step 4: Track the shipment
                if ($trackingNum = $shipment['trackingNumber'] ?? null) {
                    $this->info("\nStep 4: Track Shipment");
                    $this->testTracking($trackingNum);
                }
            }
        } else {
            $this->info("\n⏭️ Shipment creation skipped (--skip-ship)\n");
        }

        $this->newLine();
        $this->info("🎉 FedEx API Journey Test Complete!");

        return Command::SUCCESS;
    }

    /**
     * Step 1: Authenticate with FedEx OAuth 2.0
     */
    private function authenticate(): bool
    {
        $this->line("   POST /oauth/token");

        try {
            $response = Http::asForm()
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->config['client_id'],
                    'client_secret' => $this->config['client_secret'],
                ]);

            if ($response->failed()) {
                $this->error("   ❌ Auth failed: " . $response->body());
                return false;
            }

            $data = $response->json();
            $this->token = $data['access_token'];
            $this->line("   Token expires in: " . ($data['expires_in'] ?? 'unknown') . " seconds");

            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Auth error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Step 2: Get Rate Quotes
     */
    private function getRates(): ?array
    {
        $this->line("   POST /rate/v1/rates/quotes");

        $payload = [
            'accountNumber' => [
                'value' => $this->config['account_number'],
            ],
            'requestedShipment' => [
                'shipper' => [
                    'address' => [
                        'postalCode' => '38118',
                        'countryCode' => 'US',
                    ],
                ],
                'recipient' => [
                    'address' => [
                        'postalCode' => '10001',
                        'countryCode' => 'US',
                    ],
                ],
                'pickupType' => 'DROPOFF_AT_FEDEX_LOCATION',
                'rateRequestType' => ['LIST', 'ACCOUNT'],
                'requestedPackageLineItems' => [
                    [
                        'weight' => [
                            'units' => 'LB',
                            'value' => 5.0,
                        ],
                        'dimensions' => [
                            'length' => 10,
                            'width' => 8,
                            'height' => 6,
                            'units' => 'IN',
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($this->token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/rate/v1/rates/quotes", $payload);

            $this->line("   Status: " . $response->status());

            if ($response->failed()) {
                $body = $response->json();
                $this->error("   ❌ Rate request failed");
                $this->line("   Response: " . json_encode($body, JSON_PRETTY_PRINT));
                return null;
            }

            $data = $response->json();
            return $data['output']['rateReplyDetails'] ?? [];

        } catch (\Exception $e) {
            $this->error("   ❌ Rate error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Display rate results
     */
    private function displayRates(array $rates): void
    {
        $rows = [];
        foreach (array_slice($rates, 0, 5) as $rate) {
            $charges = $rate['ratedShipmentDetails'][0]['totalNetCharge'] ?? null;
            $rows[] = [
                $rate['serviceType'] ?? 'N/A',
                $rate['serviceName'] ?? 'N/A',
                $charges ? '$' . number_format($charges, 2) : 'N/A',
                $rate['commit']['dateDetail']['dayOfWeek'] ?? 'N/A',
            ];
        }

        if (!empty($rows)) {
            $this->table(['Service Type', 'Service Name', 'Total', 'Delivery Day'], $rows);
        }
    }

    /**
     * Step 3: Create a test shipment
     */
    private function createShipment(): ?array
    {
        $this->line("   POST /ship/v1/shipments");

        $payload = [
            'labelResponseOptions' => 'URL_ONLY',
            'requestedShipment' => [
                'shipper' => [
                    'contact' => [
                        'personName' => 'Test Shipper',
                        'phoneNumber' => '9015551234',
                        'companyName' => 'Test Company',
                    ],
                    'address' => [
                        'streetLines' => ['123 Test Street'],
                        'city' => 'Memphis',
                        'stateOrProvinceCode' => 'TN',
                        'postalCode' => '38118',
                        'countryCode' => 'US',
                    ],
                ],
                'recipients' => [
                    [
                        'contact' => [
                            'personName' => 'Test Recipient',
                            'phoneNumber' => '2125551234',
                            'companyName' => 'Recipient Corp',
                        ],
                        'address' => [
                            'streetLines' => ['456 Broadway'],
                            'city' => 'New York',
                            'stateOrProvinceCode' => 'NY',
                            'postalCode' => '10001',
                            'countryCode' => 'US',
                        ],
                    ],
                ],
                'shipDatestamp' => now()->format('Y-m-d'),
                'serviceType' => 'FEDEX_GROUND',
                'packagingType' => 'YOUR_PACKAGING',
                'pickupType' => 'DROPOFF_AT_FEDEX_LOCATION',
                'blockInsightVisibility' => false,
                'shippingChargesPayment' => [
                    'paymentType' => 'SENDER',
                    'payor' => [
                        'responsibleParty' => [
                            'accountNumber' => [
                                'value' => $this->config['account_number'],
                            ],
                        ],
                    ],
                ],
                'labelSpecification' => [
                    'imageType' => 'PDF',
                    'labelStockType' => 'PAPER_4X6',
                ],
                'requestedPackageLineItems' => [
                    [
                        'weight' => [
                            'value' => 5.0,
                            'units' => 'LB',
                        ],
                        'dimensions' => [
                            'length' => 10,
                            'width' => 8,
                            'height' => 6,
                            'units' => 'IN',
                        ],
                    ],
                ],
            ],
            'accountNumber' => [
                'value' => $this->config['account_number'],
            ],
        ];

        try {
            $response = Http::withToken($this->token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/ship/v1/shipments", $payload);

            $this->line("   Status: " . $response->status());

            $data = $response->json();

            if ($response->failed()) {
                $this->error("   ❌ Shipment creation failed");
                if (isset($data['errors'])) {
                    foreach ($data['errors'] as $error) {
                        $this->line("   - [{$error['code']}] {$error['message']}");
                    }
                }
                return null;
            }

            $transaction = $data['output']['transactionShipments'][0] ?? [];

            return [
                'trackingNumber' => $transaction['pieceResponses'][0]['trackingNumber'] ?? null,
                'masterTrackingNumber' => $transaction['masterTrackingNumber'] ?? null,
                'serviceType' => $transaction['serviceType'] ?? null,
                'labelUrl' => $transaction['pieceResponses'][0]['packageDocuments'][0]['url'] ?? null,
            ];

        } catch (\Exception $e) {
            $this->error("   ❌ Shipment error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Step 4: Track a shipment
     */
    private function testTracking(string $trackingNumber): int
    {
        $this->line("   POST /track/v1/trackingnumbers");
        $this->line("   Tracking: {$trackingNumber}");

        // Authenticate first if needed
        if (empty($this->token)) {
            $this->authenticate();
        }

        $payload = [
            'includeDetailedScans' => true,
            'trackingInfo' => [
                [
                    'trackingNumberInfo' => [
                        'trackingNumber' => $trackingNumber,
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($this->token)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}/track/v1/trackingnumbers", $payload);

            $this->line("   Status: " . $response->status());

            $data = $response->json();

            if ($response->failed()) {
                $this->error("   ❌ Tracking failed");
                $this->line("   " . json_encode($data, JSON_PRETTY_PRINT));
                return Command::FAILURE;
            }

            $trackResult = $data['output']['completeTrackResults'][0]['trackResults'][0] ?? [];
            $latestStatus = $trackResult['latestStatusDetail'] ?? [];

            $this->info("   ✅ Tracking successful!");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Status', $latestStatus['statusByLocale'] ?? $latestStatus['code'] ?? 'Unknown'],
                    ['Description', $latestStatus['description'] ?? 'N/A'],
                    ['Location', $trackResult['lastestLocation']['locationContactAndAddress']['address']['city'] ?? 'N/A'],
                    ['Est. Delivery', $trackResult['estimatedDeliveryTimeWindow']['window']['ends'] ?? 'N/A'],
                ]
            );

            // Show recent events
            $events = $trackResult['scanEvents'] ?? [];
            if (!empty($events)) {
                $this->line("\n   Recent Events:");
                foreach (array_slice($events, 0, 5) as $event) {
                    $this->line("   - [{$event['date']}] {$event['eventDescription']}");
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("   ❌ Tracking error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
