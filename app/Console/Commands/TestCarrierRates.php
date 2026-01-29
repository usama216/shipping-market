<?php

namespace App\Console\Commands;

use App\Carriers\CarrierFactory;
use App\Carriers\DTOs\Address;
use App\Carriers\DTOs\PackageDetail;
use App\Carriers\DTOs\ShipmentRequest;
use App\Services\ShippingRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCarrierRates extends Command
{
    protected $signature = 'carrier:test-rates 
                            {--carrier=fedex : Carrier to test (fedex, dhl, ups)}
                            {--weight=5 : Package weight in lbs}
                            {--country=VG : Destination country code (e.g., VG for British Virgin Islands)}
                            {--city=Road+Town : Destination city}
                            {--state= : Destination state (optional)}
                            {--zip= : Destination postal code (optional)}';

    protected $description = 'Test carrier rate API endpoint with international destinations';

    public function handle(ShippingRateService $shippingRateService): int
    {
        $carrier = $this->option('carrier');
        $weight = (float) $this->option('weight');
        $country = $this->option('country');
        $city = $this->option('city');
        $state = $this->option('state');
        $zip = $this->option('zip');

        $this->info("=== Carrier Rate Test ===");
        $this->info("Carrier: {$carrier}");
        $this->info("Weight: {$weight} lbs");
        $this->info("Destination: {$city}, {$state} {$zip}, {$country}");
        $this->newLine();

        // Test destination: US to British Virgin Islands
        $destination = [
            'street1' => '1 Main Street',
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => $country,
        ];

        $dimensions = [
            'length' => 12,
            'width' => 10,
            'height' => 8,
        ];

        $this->info("1. Testing ShippingRateService->getAllRates()...");
        $this->newLine();

        try {
            $rates = $shippingRateService->getAllRates($carrier, $weight, $dimensions, $destination);

            if (empty($rates)) {
                $this->warn("No rates returned from API.");
            } else {
                $this->info("Received " . count($rates) . " rate option(s):");
                $this->newLine();

                foreach ($rates as $rate) {
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Service Name', $rate['service_name'] ?? 'N/A'],
                            ['Service Code', $rate['service_code'] ?? 'N/A'],
                            ['Price', '$' . number_format($rate['price'] ?? 0, 2)],
                            ['Currency', $rate['currency'] ?? 'USD'],
                            ['Transit Days', $rate['transit_days'] ?? 'N/A'],
                            ['Delivery Date', $rate['delivery_date'] ?? 'N/A'],
                            ['Is Live Rate', ($rate['is_live_rate'] ?? false) ? 'Yes' : 'No'],
                            ['Carrier', $rate['carrier'] ?? 'N/A'],
                        ]
                    );
                    $this->newLine();
                }
            }
        } catch (\Exception $e) {
            $this->error("Error fetching rates: " . $e->getMessage());
            Log::error('TestCarrierRates error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $this->newLine();
        $this->info("2. Testing getAllCarrierRates() for all carriers...");
        $this->newLine();

        try {
            $allRates = $shippingRateService->getAllCarrierRates($weight, $dimensions, $destination);

            foreach ($allRates as $carrierName => $carrierData) {
                $this->info("=== {$carrierData['name']} ===");
                $this->line("Enabled: " . ($carrierData['enabled'] ? 'Yes' : 'No'));

                if (isset($carrierData['error'])) {
                    $this->warn("Error: " . $carrierData['error']);
                }

                if (isset($carrierData['is_fallback']) && $carrierData['is_fallback']) {
                    $this->warn("(Using fallback/database rates)");
                }

                if (!empty($carrierData['rates'])) {
                    $this->info("Rates:");
                    foreach ($carrierData['rates'] as $rate) {
                        $this->line(sprintf(
                            "  - %s (%s): $%.2f | %s days",
                            $rate['service_name'] ?? 'N/A',
                            $rate['service_code'] ?? 'N/A',
                            $rate['price'] ?? 0,
                            $rate['transit_days'] ?? 'N/A'
                        ));
                    }
                } else {
                    $this->warn("No rates available");
                }
                $this->newLine();
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }

        $this->newLine();
        $this->info("3. Testing direct FedEx API call...");
        $this->newLine();

        try {
            $fedex = CarrierFactory::make('fedex');

            // Build request directly
            $senderAddress = new Address(
                street1: config('carriers.default_sender_address', '7900 NW 25th St'),
                street2: null,
                city: config('carriers.default_sender_city', 'Miami'),
                state: config('carriers.default_sender_state', 'FL'),
                postalCode: config('carriers.default_sender_zip', '33122'),
                countryCode: config('carriers.default_sender_country', 'US'),
            );

            $recipientAddress = new Address(
                street1: '1 Main Street',
                street2: null,
                city: $city,
                state: $state ?: '',
                postalCode: $zip ?: '',
                countryCode: $country,
            );

            $package = new PackageDetail(
                weight: $weight,
                weightUnit: 'LB',
                length: 12,
                width: 10,
                height: 8,
                dimensionUnit: 'IN',
                declaredValue: null,
            );

            $request = new ShipmentRequest(
                senderName: 'Marketz Warehouse',
                senderCompany: 'Marketz LLC',
                senderPhone: '3051234567',
                senderEmail: 'shipping@marketz.com',
                senderAddress: $senderAddress,
                recipientName: 'Test Customer',
                recipientPhone: '1234567890',
                recipientEmail: 'test@example.com',
                recipientAddress: $recipientAddress,
                packages: [$package],
                serviceType: null, // Get all rates
            );

            $this->info("Calling FedEx API directly...");
            $this->line("Sender: {$senderAddress->city}, {$senderAddress->state} {$senderAddress->postalCode}, {$senderAddress->countryCode}");
            $this->line("Recipient: {$recipientAddress->city}, {$recipientAddress->state} {$recipientAddress->postalCode}, {$recipientAddress->countryCode}");
            $this->newLine();

            $rates = $fedex->getRates($request);

            if (empty($rates)) {
                $this->warn("No rates returned from FedEx API.");
            } else {
                $this->info("FedEx API returned " . count($rates) . " rate(s):");
                $this->newLine();

                foreach ($rates as $rate) {
                    $this->line(sprintf(
                        "  - %s: $%.2f | %s days | %s",
                        $rate->serviceName ?? $rate->serviceType ?? 'N/A',
                        $rate->totalCharge ?? 0,
                        $rate->transitDays ?? 'N/A',
                        $rate->currency ?? 'USD'
                    ));
                }
            }
        } catch (\Exception $e) {
            $this->error("FedEx API Error: " . $e->getMessage());

            if (method_exists($e, 'getErrors')) {
                $errors = $e->getErrors();
                if (!empty($errors)) {
                    $this->error("API Errors:");
                    foreach ($errors as $error) {
                        $this->line("  - " . (is_array($error) ? json_encode($error) : $error));
                    }
                }
            }
        }

        $this->newLine();
        $this->info("Test complete!");

        return Command::SUCCESS;
    }
}
