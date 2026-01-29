<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ship;
use App\Models\Package;
use App\Models\UserCard;
use App\Services\CheckoutService;
use App\Services\DTOs\CheckoutRequest;
use App\Services\DTOs\CheckoutResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected CheckoutService $checkoutService;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a customer with required fields
        $this->customer = Customer::factory()->create([
            'stripe_id' => 'cus_test_' . $this->faker->uuid,
            'loyalty_points' => 100,
        ]);
    }

    /** @test */
    public function it_returns_failure_when_shipment_not_found()
    {
        $checkoutService = app(CheckoutService::class);

        $request = CheckoutRequest::fromArray([
            'id' => 99999, // Non-existent shipment
            'card_id' => 1,
            'estimated_shipping_charges' => 25.00,
            'customer_address_id' => 1,
            'loyalty_points_used' => 0,
            'loyalty_discount' => 0,
        ]);

        $result = $checkoutService->processCheckout($this->customer, $request);

        $this->assertFalse($result->success);
        $this->assertEquals('Shipment not found.', $result->error);
    }

    /** @test */
    public function it_returns_failure_when_shipment_belongs_to_different_customer()
    {
        $checkoutService = app(CheckoutService::class);

        // Create another customer and their shipment
        $otherCustomer = Customer::factory()->create();
        $ship = Ship::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $request = CheckoutRequest::fromArray([
            'id' => $ship->id,
            'card_id' => 1,
            'estimated_shipping_charges' => 25.00,
            'customer_address_id' => 1,
            'loyalty_points_used' => 0,
            'loyalty_discount' => 0,
        ]);

        $result = $checkoutService->processCheckout($this->customer, $request);

        $this->assertFalse($result->success);
        $this->assertEquals('Unauthorized access to shipment.', $result->error);
    }

    /** @test */
    public function it_returns_failure_when_payment_method_not_found()
    {
        $checkoutService = app(CheckoutService::class);

        $ship = Ship::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $request = CheckoutRequest::fromArray([
            'id' => $ship->id,
            'card_id' => 99999, // Non-existent card
            'estimated_shipping_charges' => 25.00,
            'customer_address_id' => 1,
            'loyalty_points_used' => 0,
            'loyalty_discount' => 0,
        ]);

        $result = $checkoutService->processCheckout($this->customer, $request);

        $this->assertFalse($result->success);
        $this->assertStringContainsString('Invalid payment method', $result->error);
    }

    /** @test */
    public function checkout_request_dto_creates_from_array()
    {
        $data = [
            'id' => 1,
            'card_id' => 2,
            'estimated_shipping_charges' => 29.99,
            'customer_address_id' => 3,
            'loyalty_points_used' => 50,
            'loyalty_discount' => 5.00,
            'international_shipping_option_id' => 'fedex_express',
            'packing_option_id' => [1, 2],
            'shipping_preference_option_id' => [3],
            'subtotal' => 35.00,
        ];

        $request = CheckoutRequest::fromArray($data);

        $this->assertEquals(1, $request->shipId);
        $this->assertEquals(2, $request->cardId);
        $this->assertEquals(29.99, $request->estimatedShippingCharges);
        $this->assertEquals(3, $request->customerAddressId);
        $this->assertEquals(50, $request->loyaltyPointsUsed);
        $this->assertEquals(5.00, $request->loyaltyDiscountAmount);
    }

    /** @test */
    public function checkout_result_dto_success_factory()
    {
        $ship = new Ship(['id' => 1]);

        $result = CheckoutResult::success($ship);

        $this->assertTrue($result->success);
        $this->assertSame($ship, $result->ship);
        $this->assertNull($result->error);
    }

    /** @test */
    public function checkout_result_dto_failure_factory()
    {
        $result = CheckoutResult::failure('Something went wrong');

        $this->assertFalse($result->success);
        $this->assertNull($result->ship);
        $this->assertEquals('Something went wrong', $result->error);
    }

    /** @test */
    public function checkout_result_provides_redirect_route()
    {
        $ship = new Ship(['id' => 42]);

        $result = CheckoutResult::success($ship);

        $this->assertEquals(
            route('customer.shipment.success', ['shipId' => 42]),
            $result->getRedirectRoute()
        );
    }
}
