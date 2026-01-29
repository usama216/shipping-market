<?php

namespace App\Traits;

use App\Models\Coupon;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use App\Services\CouponService;
use App\Services\LoyaltyService;
use Illuminate\Support\Facades\DB;

trait LoyaltyCouponTrait
{
    /**
     * Apply coupon and loyalty discounts to an order
     */
    public function applyDiscountsToOrder(
        User $user,
        float $orderAmount,
        ?string $couponCode = null,
        ?int $loyaltyPoints = null
    ): array {
        $couponDiscount = 0;
        $loyaltyDiscount = 0;
        $appliedCoupon = null;
        $appliedLoyaltyPoints = 0;
        $errors = [];

        // Apply coupon if provided
        if ($couponCode) {
            $couponService = app(CouponService::class);
            $couponResult = $couponService->validateAndApplyCoupon($couponCode, $user, $orderAmount);

            if ($couponResult['success']) {
                $couponDiscount = $couponResult['discount'];
                $appliedCoupon = $couponResult['coupon'];
            } else {
                $errors[] = $couponResult['message'];
            }
        }

        // Apply loyalty points if provided
        if ($loyaltyPoints && $loyaltyPoints > 0) {
            $loyaltyService = app(LoyaltyService::class);
            $loyaltyResult = $loyaltyService->calculateLoyaltyDiscount($loyaltyPoints, $orderAmount - $couponDiscount);

            if ($loyaltyResult['success']) {
                $loyaltyDiscount = $loyaltyResult['discount'];
                $appliedLoyaltyPoints = $loyaltyResult['points_required'];
            } else {
                $errors[] = $loyaltyResult['message'];
            }
        }

        $totalDiscount = $couponDiscount + $loyaltyDiscount;
        $finalAmount = max(0, $orderAmount - $totalDiscount);

        return [
            'original_amount' => $orderAmount,
            'coupon_discount' => $couponDiscount,
            'loyalty_discount' => $loyaltyDiscount,
            'total_discount' => $totalDiscount,
            'final_amount' => $finalAmount,
            'applied_coupon' => $appliedCoupon,
            'applied_loyalty_points' => $appliedLoyaltyPoints,
            'errors' => $errors,
            'success' => empty($errors)
        ];
    }

    /**
     * Process order completion with loyalty points earning
     */
    public function processOrderCompletion(
        User $user,
        float $orderAmount,
        ?Coupon $coupon = null,
        ?int $loyaltyPointsRedeemed = null
    ): bool {
        try {
            DB::transaction(function () use ($user, $orderAmount, $coupon, $loyaltyPointsRedeemed) {
                $loyaltyService = app(LoyaltyService::class);
                $couponService = app(CouponService::class);

                // Earn loyalty points for the purchase
                $loyaltyService->earnPoints($user, null, $orderAmount);

                // Record coupon usage if applicable
                if ($coupon) {
                    $couponService->recordCouponUsage($coupon, $user, null, $coupon->calculateDiscount($orderAmount), $orderAmount);
                }

                // Record loyalty points redemption if applicable
                if ($loyaltyPointsRedeemed && $loyaltyPointsRedeemed > 0) {
                    $rule = LoyaltyRule::getDefaultRule();
                    if ($rule) {
                        $discountAmount = $rule->calculateRedeemValue($loyaltyPointsRedeemed);
                        $loyaltyService->redeemPoints($user, null, $loyaltyPointsRedeemed, $discountAmount);
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get user's loyalty summary
     */
    public function getUserLoyaltySummary(User $user): array
    {
        $loyaltyService = app(LoyaltyService::class);
        return $loyaltyService->getUserLoyaltySummary($user);
    }

    /**
     * Get available coupons for a user
     */
    public function getAvailableCoupons(User $user): array
    {
        return Coupon::active()
            ->valid()
            ->available()
            ->whereDoesntHave('usages', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->get()
            ->toArray();
    }

    /**
     * Validate if user can redeem loyalty points
     */
    public function canRedeemLoyaltyPoints(User $user, int $points, float $orderAmount): array
    {
        if (!$user->hasEnoughLoyaltyPoints($points)) {
            return [
                'success' => false,
                'message' => 'Insufficient loyalty points.'
            ];
        }

        $rule = LoyaltyRule::getDefaultRule();
        if (!$rule) {
            return [
                'success' => false,
                'message' => 'Loyalty program is not configured.'
            ];
        }

        $maxRedeemable = $rule->getMaxRedeemablePoints($orderAmount);
        if ($points > $maxRedeemable) {
            return [
                'success' => false,
                'message' => "Maximum redeemable points for this order: {$maxRedeemable}."
            ];
        }

        return [
            'success' => true,
            'message' => 'Points can be redeemed.'
        ];
    }

    /**
     * Get loyalty program statistics
     */
    public function getLoyaltyStats(): array
    {
        $loyaltyService = app(LoyaltyService::class);
        return $loyaltyService->getLoyaltyStats();
    }

    /**
     * Get coupon usage statistics
     */
    public function getCouponStats(): array
    {
        $couponService = app(CouponService::class);
        return $couponService->getCouponUsageStats();
    }
}
