<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class CouponService
{
    /**
     * Validate and apply a coupon
     */
    public function validateAndApplyCoupon(string $code, \App\Models\Customer $customer, float $orderAmount): array
    {
        try {
            // Find the coupon
            $coupon = Coupon::where('code', $code)->first();

            if (!$coupon) {
                return [
                    'success' => false,
                    'message' => 'Invalid coupon code.',
                    'discount' => 0
                ];
            }

            // Check if coupon is valid (removed usage_limit check from is_valid)
            if (!$coupon->is_active || $coupon->is_expired || !$coupon->is_started) {
                return [
                    'success' => false,
                    'message' => 'This coupon is no longer valid.',
                    'discount' => 0
                ];
            }

            // Check if coupon is assigned to a specific customer
            if ($coupon->assigned_customer_id !== null && $coupon->assigned_customer_id !== $customer->id) {
                return [
                    'success' => false,
                    'message' => 'This coupon is not available for your account.',
                    'discount' => 0
                ];
            }

            // Note: Minimum order amount check removed per requirements

            // Check if customer has exceeded their usage limit for this coupon
            $customerUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('customer_id', $customer->id)
                ->count();

            // If per_customer_limit is set, check against it; otherwise allow only 1 use
            $perCustomerLimit = $coupon->per_customer_limit ?? 1;

            if ($customerUsageCount >= $perCustomerLimit) {
                $message = $perCustomerLimit === 1
                    ? 'You have already used this coupon.'
                    : "You have reached the maximum usage limit ({$perCustomerLimit} times) for this coupon.";
                return [
                    'success' => false,
                    'message' => $message,
                    'discount' => 0
                ];
            }

            // Check target audience
            if (!$this->isEligibleForAudience($coupon, $customer)) {
                return [
                    'success' => false,
                    'message' => 'You are not eligible for this coupon.',
                    'discount' => 0
                ];
            }

            // Calculate discount
            $discount = $coupon->calculateDiscount($orderAmount);

            return [
                'success' => true,
                'message' => 'Coupon applied successfully!',
                'discount' => $discount,
                'coupon' => $coupon
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'An error occurred while validating the coupon.',
                'discount' => 0
            ];
        }
    }

    /**
     * Get auto-applicable coupons for a customer
     */
    public function getAutoApplicableCoupons(\App\Models\Customer $customer, float $orderAmount): \Illuminate\Support\Collection
    {
            return Coupon::active()
            ->where('auto_apply', true)
            ->get()
            ->filter(function ($coupon) use ($customer, $orderAmount) {
                // Basic checks (removed minimum_order_amount check)
                if (!$coupon->is_active || $coupon->is_expired || !$coupon->is_started) {
                    return false;
                }

                // Audience check
                if (!$this->isEligibleForAudience($coupon, $customer)) {
                    return false;
                }

                // Usage check - respect per_customer_limit
                $usageCount = CouponUsage::where('coupon_id', $coupon->id)
                    ->where('customer_id', $customer->id)
                    ->count();

                $perCustomerLimit = $coupon->per_customer_limit ?? 1;
                return $usageCount < $perCustomerLimit;
            })
            ->sortByDesc(function ($coupon) use ($orderAmount) {
                return $coupon->calculateDiscount($orderAmount);
            });
    }

    /**
     * Check if customer is eligible for coupon audience
     */
    private function isEligibleForAudience(Coupon $coupon, \App\Models\Customer $customer): bool
    {
        if ($coupon->target_audience === 'all') {
            return true;
        }

        if ($coupon->target_audience === 'new_customer') {
            // Check if customer has any completed transactions
            $hasTransactions = Transaction::where('customer_id', $customer->id)->exists();
            return !$hasTransactions;
        }

        if ($coupon->target_audience === 'registration') {
            // Logic for registration coupons (usually handled at signup, but can be checked here if within time window)
            return $customer->created_at->diffInDays(now()) < 7;
        }

        if ($coupon->target_audience === 'certain_customers') {
            // Check if customer is in the selected customer IDs list
            $selectedCustomerIds = $coupon->selected_customer_ids ?? [];
            if (empty($selectedCustomerIds)) {
                return false; // No customers selected, so no one is eligible
            }
            return in_array($customer->id, $selectedCustomerIds);
        }

        return true;
    }

    /**
     * Record coupon usage
     */
    public function recordCouponUsage(Coupon $coupon, \App\Models\Customer $customer, Transaction $transaction, float $discountAmount, float $orderAmount): bool
    {
        try {
            DB::transaction(function () use ($coupon, $customer, $transaction, $discountAmount, $orderAmount) {
                // Create usage record
                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'customer_id' => $customer->id,
                    'transaction_id' => $transaction->id,
                    'discount_amount' => $discountAmount,
                    'order_amount' => $orderAmount
                ]);

                // Increment coupon usage count
                $coupon->incrementUsage();
            });

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get coupon usage statistics
     */
    public function getCouponUsageStats(): array
    {
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::active()->count();
        $expiredCoupons = Coupon::where('expiry_date', '<', now())->count();
        $totalUsage = CouponUsage::count();

        return [
            'total_coupons' => $totalCoupons,
            'active_coupons' => $activeCoupons,
            'expired_coupons' => $expiredCoupons,
            'total_usage' => $totalUsage
        ];
    }

    /**
     * Get coupon usage by user
     */
    public function getUserCouponUsage(\App\Models\Customer $customer): array
    {
        return CouponUsage::with('coupon')
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Generate unique coupon code
     */
    public function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length));
        } while (Coupon::where('code', $code)->exists());

        return $code;
    }
}
