<?php

namespace App\Console\Commands;

use App\Carriers\CarrierFactory;
use App\Carriers\DTOs\Address;
use App\Carriers\DTOs\PackageDetail;
use App\Carriers\DTOs\ShipmentRequest;
use App\Carriers\Exceptions\CarrierException;
use Illuminate\Console\Command;

/**
 * Test carrier API connection and authentication
 * 
 * Usage: php artisan carrier:test fedex
 */
class TestCarrierConnection extends Command
{
    protected $signature = 'carrier:test {carrier=fedex : Carrier to test (fedex, dhl, ups)}';
    protected $description = 'Test carrier API authentication and connection';

    public function handle(): int
    {
        $carrierName = $this->argument('carrier');

        $this->info("Testing {$carrierName} API connection...\n");

        try {
            // Get carrier instance
            $carrier = CarrierFactory::make($carrierName);
            $this->line("✓ Carrier instance created: {$carrier->getName()}");

            // Test authentication
            $this->line("Authenticating with {$carrierName}...");
            $authenticated = $carrier->authenticate();

            if ($authenticated) {
                $this->info("✓ Authentication successful!");
            }

            // Test rate request with sample data
            $this->line("\nTesting rate calculation...");

            $request = $this->buildSampleRequest();
            $rates = $carrier->getRates($request);

            if (count($rates) > 0) {
                $this->info("✓ Rate calculation successful! Found " . count($rates) . " rates:\n");

                $this->table(
                    ['Service', 'Carrier', 'Total', 'Currency', 'Transit Days'],
                    array_map(fn($rate) => [
                        $rate->serviceName,
                        $rate->carrierName,
                        $rate->totalCharge,
                        $rate->currency,
                        $rate->transitDays ?? 'N/A',
                    ], $rates)
                );
            } else {
                $this->warn("⚠ No rates returned (this may be expected for sandbox)");
            }

            $this->newLine();
            $this->info("🎉 All tests passed! {$carrierName} is configured correctly.");

            return Command::SUCCESS;

        } catch (CarrierException $e) {
            $this->error("\n✗ Carrier API Error: " . $e->getMessage());

            if ($errors = $e->getErrors()) {
                $this->line("\nError details:");
                foreach ($errors as $key => $value) {
                    $this->line("  - {$key}: " . (is_array($value) ? json_encode($value) : $value));
                }
            }

            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error("\n✗ Error: " . $e->getMessage());
            $this->line("\nStack trace:");
            $this->line($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Build a sample shipment request for testing
     */
    private function buildSampleRequest(): ShipmentRequest
    {
        // Use default sender from config
        $senderAddress = new Address(
            street1: config('carriers.default_sender_address', '123 Test St'),
            city: config('carriers.default_sender_city', 'Memphis'),
            state: config('carriers.default_sender_state', 'TN'),
            postalCode: config('carriers.default_sender_zip', '38118'),
            countryCode: config('carriers.default_sender_country', 'US'),
        );

        // Sample recipient - New York
        $recipientAddress = new Address(
            street1: '456 Broadway',
            city: 'New York',
            state: 'NY',
            postalCode: '10013',
            countryCode: 'US',
        );

        // Sample package
        $package = new PackageDetail(
            weight: 2.5,
            length: 10,
            width: 8,
            height: 6,
            weightUnit: 'LB',
            dimensionUnit: 'IN',
        );

        return new ShipmentRequest(
            senderName: config('carriers.default_sender_name', 'Test Sender'),
            senderCompany: config('carriers.default_sender_company', 'Test Company'),
            senderPhone: config('carriers.default_sender_phone', '9015551234'),
            senderEmail: config('carriers.default_sender_email', 'test@example.com'),
            senderAddress: $senderAddress,
            recipientName: 'Test Recipient',
            recipientPhone: '2125551234',
            recipientEmail: 'recipient@example.com',
            recipientAddress: $recipientAddress,
            packages: [$package],
            serviceType: 'FEDEX_GROUND',
            packagingType: 'YOUR_PACKAGING',
        );
    }
}
