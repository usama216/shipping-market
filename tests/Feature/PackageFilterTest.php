<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use App\Helpers\PackageStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_package_index_accepts_filter_parameters()
    {
        // Create test data
        $admin = User::factory()->create(['type' => User::USER_TYPE_ADMIN]);
        $customer = Customer::factory()->create();

        $package1 = Package::factory()->create([
            'status' => PackageStatus::ACTION_REQUIRED,
            'customer_id' => $customer->id,
            'tracking_id' => 'TRACK123',
            'total_value' => 100.00,
            'date_received' => '2025-01-15'
        ]);

        $package2 = Package::factory()->create([
            'status' => PackageStatus::READY_TO_SEND,
            'customer_id' => $customer->id,
            'tracking_id' => 'TRACK456',
            'total_value' => 200.00,
            'date_received' => '2025-01-20'
        ]);

        $response = $this->actingAs($admin)
            ->get('/package?status=1&tracking_id=TRACK123');

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) =>
            $page->component('Package/Report')
                ->has('packages')
                ->has('customers')
                ->has('filters')
        );
    }

    public function test_package_filters_work_correctly()
    {
        $admin = User::factory()->create(['type' => User::USER_TYPE_ADMIN]);
        $customer = Customer::factory()->create();

        // Create packages with different statuses
        Package::factory()->create([
            'status' => PackageStatus::ACTION_REQUIRED,
            'customer_id' => $customer->id,
            'tracking_id' => 'TRACK123',
            'total_value' => 100.00,
            'date_received' => '2025-01-15'
        ]);

        Package::factory()->create([
            'status' => PackageStatus::READY_TO_SEND,
            'customer_id' => $customer->id,
            'tracking_id' => 'TRACK456',
            'total_value' => 200.00,
            'date_received' => '2025-01-20'
        ]);

        // Test status filter
        $response = $this->actingAs($admin)
            ->get('/package?status=1');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('packages')->count());

        // Test tracking ID filter
        $response = $this->actingAs($admin)
            ->get('/package?tracking_id=TRACK123');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->viewData('packages')->count());
    }

    public function test_package_filters_validation()
    {
        $admin = User::factory()->create(['type' => User::USER_TYPE_ADMIN]);

        // Test invalid status
        $response = $this->actingAs($admin)
            ->get('/package?status=999');

        $response->assertStatus(302); // Redirect due to validation error

        // Test invalid date range
        $response = $this->actingAs($admin)
            ->get('/package?date_from=2025-01-20&date_to=2025-01-15');

        $response->assertStatus(302); // Redirect due to validation error
    }
}
