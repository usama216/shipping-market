<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function __construct(
        private CouponService $couponService
    ) {
    }

    /**
     * Validate and apply coupon
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'order_amount' => 'required|numeric|min:0'
        ]);

        /** @var \App\Models\Customer|null $customer */
        $customer = Auth::guard('customer')->user();
        $code = strtoupper($request->code);
        $orderAmount = (float) $request->order_amount;

        $result = $this->couponService->validateAndApplyCoupon($code, $customer, $orderAmount);

        return response()->json($result);
    }

    /**
     * Get customer's coupon usage history
     */
    public function history(): JsonResponse
    {
        /** @var \App\Models\Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $usage = $this->couponService->getUserCouponUsage($customer);

        return response()->json([
            'success' => true,
            'usage' => $usage
        ]);
    }

    /**
     * Check for auto-applicable coupons
     */
    public function autoApply(Request $request): JsonResponse
    {
        $request->validate([
            'order_amount' => 'required|numeric|min:0'
        ]);

        /** @var \App\Models\Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $orderAmount = (float) $request->order_amount;

        $coupons = $this->couponService->getAutoApplicableCoupons($customer, $orderAmount);

        if ($coupons->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No auto-apply coupons available.'
            ]);
        }

        // Apply the best one (first one as logic sorts by discount)
        $bestCoupon = $coupons->first();
        $discount = $bestCoupon->calculateDiscount($orderAmount);

        return response()->json([
            'success' => true,
            'message' => 'Coupon automatically applied!',
            'coupon' => $bestCoupon,
            'discount' => $discount
        ]);
    }
}
