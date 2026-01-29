<?php

namespace Tests\Feature;

use App\Helpers\ShipmentStatus;
use App\Helpers\CarrierStatus;
use App\Models\Customer;
use App\Models\Ship;
use App\Models\ShipmentEvent;
use App\Services\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();
    }

    /** @test */
    public function customer_can_view_tracking_page()
    {
        $ship = Ship::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => ShipmentStatus::SHIPPED,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.tracking.index'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->component('Customers/OrderTracking/Index')
        );
    }

    /** @test */
    public function customer_can_view_shipment_tracking_details()
    {
        $ship = Ship::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => ShipmentStatus::SHIPPED,
            'carrier_tracking_number' => 'TRK123456789',
        ]);

        // Add tracking events
        ShipmentEvent::create([
            'ship_id' => $ship->id,
            'event_type' => 'shipped',
            'description' => 'Package has been shipped',
            'location' => 'Miami, FL',
            'event_date' => now(),
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.tracking.show', $ship->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function customer_cannot_view_other_customer_tracking()
    {
        $otherCustomer = Customer::factory()->create();
        $otherShip = Ship::factory()->create([
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('customer.tracking.show', $otherShip->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function tracking_service_searches_by_tracking_number()
    {
        $ship = Ship::factory()->create([
            'customer_id' => $this->customer->id,
            'carrier_tracking_number' => 'UNIQUE123',
        ]);

        $trackingService = app(TrackingService::class);
        $result = $trackingService->searchByTrackingNumber('UNIQUE123', $this->customer->id);

        $this->assertNotNull($result);
        $this->assertEquals($ship->id, $result->id);
    }

    /** @test */
    public function tracking_service_returns_null_for_other_customer()
    {
        $otherCustomer = Customer::factory()->create();
        $ship = Ship::factory()->create([
            'customer_id' => $otherCustomer->id,
            'carrier_tracking_number' => 'OTHER123',
        ]);

        $trackingService = app(TrackingService::class);
        $result = $trackingService->searchByTrackingNumber('OTHER123', $this->customer->id);

        $this->assertNull($result);
    }

    /** @test */
    public function shipment_status_helper_returns_all_statuses()
    {
        $statuses = ShipmentStatus::all();

        $this->assertIsArray($statuses);
        $this->assertContains(ShipmentStatus::PENDING, $statuses);
        $this->assertContains(ShipmentStatus::SHIPPED, $statuses);
        $this->assertContains(ShipmentStatus::DELIVERED, $statuses);
    }

    /** @test */
    public function carrier_status_helper_returns_all_statuses()
    {
        $statuses = CarrierStatus::all();

        $this->assertIsArray($statuses);
        $this->assertContains(CarrierStatus::PENDING, $statuses);
        $this->assertContains(CarrierStatus::SUBMITTED, $statuses);
        $this->assertContains(CarrierStatus::FAILED, $statuses);
    }
}
