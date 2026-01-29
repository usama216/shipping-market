<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\UserCard;
use App\Payments\Stripe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class DefaultCardSeeder extends Seeder
{
    /**
     * Seed a default Stripe test card for testing purposes.
     * 
     * This creates a test card using Stripe's test token 'tok_visa'
     * which represents a successful Visa card (4242424242424242).
     */
    public function run(): void
    {
        // Get the first customer
        $customer = Customer::first();

        if (!$customer) {
            $this->command->warn('No customer found. Please run CustomerSeeder first.');
            return;
        }

        // Check if customer already has a default card
        $existingCard = UserCard::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->first();

        if ($existingCard) {
            $this->command->info('Customer already has a default card. Skipping...');
            return;
        }

        try {
            // Set Stripe API key
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Create Stripe customer if doesn't exist
            if (empty($customer->stripe_id)) {
                $stripeCustomer = \Stripe\Customer::create([
                    'email' => $customer->email,
                    'name' => $customer->first_name . ' ' . $customer->last_name,
                    'metadata' => ['customerId' => $customer->id],
                    'source' => 'tok_visa', // Stripe test token for Visa 4242424242424242
                ]);

                $customer->update(['stripe_id' => $stripeCustomer->id]);
                $this->command->info("Created Stripe customer: {$stripeCustomer->id}");
            } else {
                // Add test card to existing customer
                $stripeCustomer = \Stripe\Customer::retrieve($customer->stripe_id);
                $stripeCustomer = \Stripe\Customer::update($customer->stripe_id, [
                    'source' => 'tok_visa',
                ]);
                $this->command->info("Added card to existing customer: {$customer->stripe_id}");
            }

            // Retrieve the customer to get default source
            $stripeCustomer = \Stripe\Customer::retrieve($customer->stripe_id);
            $defaultSource = $stripeCustomer->default_source;

            if ($defaultSource) {
                // Retrieve the source/card details
                $source = \Stripe\Source::retrieve($defaultSource);

                // Create the card in our database
                UserCard::create([
                    'customer_id' => $customer->id,
                    'card_id' => $source->id,
                    'exp_month' => $source->card->exp_month ?? '12',
                    'exp_year' => $source->card->exp_year ?? '2030',
                    'brand' => $source->card->brand ?? 'Visa',
                    'last4' => $source->card->last4 ?? '4242',
                    'card_holder_name' => $customer->first_name . ' ' . $customer->last_name,
                    'address_line1' => '123 Test Street',
                    'address_line2' => 'Suite 100',
                    'country' => 'United States',
                    'state' => 'California',
                    'city' => 'San Francisco',
                    'postal_code' => '94102',
                    'country_code' => '+1',
                    'phone_number' => '555-123-4567',
                    'is_default' => true,
                ]);

                $this->command->info("✓ Default test card added for customer: {$customer->email}");
                $this->command->info("  Card: {$source->card->brand} **** **** **** {$source->card->last4}");
            } else {
                // Fallback: create card directly in database with test data
                $this->createTestCardDirectly($customer);
            }

        } catch (\Exception $e) {
            Log::error('DefaultCardSeeder failed: ' . $e->getMessage());
            $this->command->warn('Stripe API failed, creating local test card: ' . $e->getMessage());

            // Fallback: create test card directly in database
            $this->createTestCardDirectly($customer);
        }
    }

    /**
     * Create a test card directly in the database (fallback when Stripe API fails)
     */
    private function createTestCardDirectly(Customer $customer): void
    {
        UserCard::create([
            'customer_id' => $customer->id,
            'card_id' => 'card_test_' . uniqid(), // Use 'card_' prefix, not 'tok_' since tokens can't be charged directly
            'exp_month' => '12',
            'exp_year' => '2030',
            'brand' => 'Visa',
            'last4' => '4242',
            'card_holder_name' => $customer->first_name . ' ' . $customer->last_name,
            'address_line1' => '123 Test Street',
            'address_line2' => 'Suite 100',
            'country' => 'United States',
            'state' => 'California',
            'city' => 'San Francisco',
            'postal_code' => '94102',
            'country_code' => '+1',
            'phone_number' => '555-123-4567',
            'is_default' => true,
        ]);

        $this->command->info("✓ Created test card directly for customer: {$customer->email}");
        $this->command->info("  Card: Visa **** **** **** 4242 (test mode)");
    }
}
