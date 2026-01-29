<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Package;
use App\Models\Ship;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShipmentFlowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Customer $customer;
    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Create warehouse
        $this->warehouse = Warehouse::factory()->create([
            'is_default' => true,
        ]);

        // Create a customer
        $this->customer = Customer::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'stripe_id' => 'cus_test_' . $this->faker->uuid,
        ]);
    }

    /** @test */
    public function customer_can_view_their_shipments()
    {
        // Create shipments for this customer
        $shipments = Ship::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.shipment.myShipments'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Customers/Shipment/List')
                ->has('ships.data', 3)
        );
    }

    /** @test */
    public function customer_cannot_view_other_customers_shipments()
    {
        // Create another customer with shipments
        $otherCustomer = Customer::factory()->create();
        $otherShipment = Ship::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        // Current customer should not see other customer's shipments
        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.shipment.myShipments'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->where('ships.data', [])
        );
    }

    /** @test */
    public function customer_can_create_shipment_with_packages()
    {
        // Create packages for the customer
        $packages = Package::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'received',
        ]);

        // Create an address for the customer
        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.shipment.create'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Customers/Shipment/Create')
                ->has('packages.data')
        );
    }

    /** @test */
    public function shipment_requires_packages()
    {
        $response = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.shipment.store'), [
                'packages' => [], // Empty packages
            ]);

        $response->assertSessionHasErrors('packages');
    }

    /** @test */
    public function customer_can_view_shipment_success_page()
    {
        $ship = Ship::factory()->create([
            'customer_id' => $this->customer->id,
            'invoice_status' => 'paid',
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.shipment.success', ['shipId' => $ship->id]));

        $response->assertStatus(200);
    }

    /** @test */
    public function checkout_validates_required_fields()
    {
        $ship = Ship::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->post(route('customer.shipment.checkout'), [
                'id' => $ship->id,
                // Missing required fields
            ]);

        $response->assertSessionHasErrors(['card_id', 'estimated_shipping_charges', 'customer_address_id']);
    }
}
