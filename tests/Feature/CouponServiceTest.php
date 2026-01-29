<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    private CouponService $couponService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->couponService = app(CouponService::class);
    }

    public function test_can_validate_basic_coupon()
    {
        $customer = Customer::factory()->create();
        $coupon = Coupon::create([
            'code' => 'TEST10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ]);

        $result = $this->couponService->validateAndApplyCoupon('TEST10', $customer, 100);

        $this->assertTrue($result['success']);
        $this->assertEquals(10, $result['discount']);
    }

    public function test_fails_if_expired()
    {
        $customer = Customer::factory()->create();
        $coupon = Coupon::create([
            'code' => 'EXPIRED',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'is_active' => true,
            'expiry_date' => now()->subDay(),
        ]);

        $result = $this->couponService->validateAndApplyCoupon('EXPIRED', $customer, 100);

        $this->assertFalse($result['success']);
    }

    public function test_fails_if_not_started()
    {
        $customer = Customer::factory()->create();
        $coupon = Coupon::create([
            'code' => 'FUTURE',
            'discount_type' => 'fixed',
            'discount_value' => 10,
            'is_active' => true,
            'start_date' => now()->addDay(),
        ]);

        $result = $this->couponService->validateAndApplyCoupon('FUTURE', $customer, 100);

        $this->assertFalse($result['success']);
    }

    public function test_auto_apply_fetches_best_coupon()
    {
        $customer = Customer::factory()->create();

        // Better coupon
        Coupon::create([
            'code' => 'BEST20',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'is_active' => true,
            'auto_apply' => true,
            'target_audience' => 'all'
        ]);

        // Worse coupon
        Coupon::create([
            'code' => 'OKAY10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
            'auto_apply' => true,
            'target_audience' => 'all'
        ]);

        $coupons = $this->couponService->getAutoApplicableCoupons($customer, 100);

        $this->assertCount(2, $coupons);
        $this->assertEquals('BEST20', $coupons->first()->code);
    }

    public function test_target_audience_new_customer()
    {
        // New customer (no transactions)
        $newCustomer = Customer::factory()->create();

        $coupon = Coupon::create([
            'code' => 'NEWUSER',
            'discount_type' => 'fixed',
            'discount_value' => 20,
            'is_active' => true,
            'target_audience' => 'new_customer'
        ]);

        $result = $this->couponService->validateAndApplyCoupon('NEWUSER', $newCustomer, 100);
        $this->assertTrue($result['success']);

        // Simulate transaction
        $transaction = \App\Models\Transaction::forceCreate([
            'customer_id' => $newCustomer->id,
            'amount' => 100,
            'status' => 'completed',
            'uuid' => \Illuminate\Support\Str::uuid(),
            'type' => 'payment' // Basic dummy data
        ]);

        // Should fail now
        $result = $this->couponService->validateAndApplyCoupon('NEWUSER', $newCustomer, 100);
        $this->assertFalse($result['success']);
    }
}
