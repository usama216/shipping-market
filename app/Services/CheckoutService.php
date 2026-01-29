<?php

namespace App\Services;

use App\Helpers\PackageStatus;
use App\Helpers\ShipmentStatus;
use App\Services\ShipmentSubmissionService;
use App\Models\CarrierAddon;
use App\Models\Customer;
use App\Models\Ship;
use App\Models\Transaction;
use App\Models\UserCard;
use App\Notifications\ShipmentCreatedNotification;
use App\Payments\Stripe;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\ShipRepository;
use App\Repositories\TransactionRepository;
use App\Services\DTOs\CheckoutRequest;
use App\Services\DTOs\CheckoutResult;
use App\Services\ShippingRateService;
use App\Services\CommercialInvoiceService;
use App\Models\CustomerAddress;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CheckoutService - Handles shipment checkout, payment processing, and finalization
 * 
 * Extracted from ShipController to improve testability and separation of concerns.
 */
class CheckoutService
{
    // Base handling fee (should match frontend constant)
    private const BASE_HANDLING_FEE = 10.00;

    // Tolerance for price comparison (covers floating point differences)
    private const PRICE_TOLERANCE = 0.50;

    public function __construct(
        private ShipRepository $shipRepository,
        private PaymentMethodRepository $paymentMethodRepository,
        private TransactionRepository $transactionRepository,
        private LoyaltyService $loyaltyService,
        private CouponService $couponService,
        private Stripe $stripeClient,
        private ShippingRateService $shippingRateService,
        private ShipmentSubmissionService $shipmentSubmissionService,
        private CommercialInvoiceService $commercialInvoiceService
    ) {
    }

    /**
     * Process complete checkout flow
     * 
     * @param Customer $customer The authenticated customer
     * @param CheckoutRequest $request The checkout request data
     * @return CheckoutResult The result of the checkout operation
     */
    public function processCheckout(Customer $customer, CheckoutRequest $request): CheckoutResult
    {
        try {
            DB::beginTransaction();

            // 1. Find and validate shipment
            $ship = $this->shipRepository->findById($request->shipId);
            if (!$ship) {
                return CheckoutResult::failure('Shipment not found.');
            }

            if ($ship->customer_id !== $customer->id) {
                return CheckoutResult::failure('Unauthorized access to shipment.');
            }

            // 2. Verify and recalculate price server-side (security: prevent price manipulation)
            $verifiedPrice = $this->verifyAndCalculatePrice($ship, $request);

            // 3. Update shipment with checkout details (using verified price)
            $this->updateShipmentDetails($ship, $request, $verifiedPrice);

            // 4. Validate and retrieve payment method
            $card = $this->validatePaymentMethod($customer, $request->cardId);

            // 5. Process payment (use verified price, not client-submitted)
            $stripeCharge = $this->chargeCustomer($customer, $card, $verifiedPrice, $ship);

            // 5. Record transaction
            $transaction = $this->recordTransaction($customer, $stripeCharge);

            // 6. Process coupon usage if applied
            if ($request->couponCode && $request->couponDiscount > 0) {
                $this->recordCouponUsage(
                    $customer,
                    $transaction,
                    $request->couponCode,
                    $request->couponDiscount,
                    $request->couponOrderAmount ?? $request->estimatedShippingCharges
                );
            }

            // 7. Process loyalty points if used
            if ($request->hasLoyaltyDiscount()) {
                $this->applyLoyaltyDiscount(
                    $customer,
                    $transaction,
                    $request->loyaltyPointsUsed,
                    $request->loyaltyDiscount
                );
            }

            // 8. Update package statuses
            $this->updatePackageStatuses($ship);

            // 9. Mark shipment as paid and update status
            $ship->invoice_status = 'paid';
            $ship->status = ShipmentStatus::PAID;
            $ship->save();

            // 10. Award loyalty points for ship request completion
            $this->loyaltyService->awardPointsForShipRequest($customer, $verifiedPrice);

            // 11. Check and award milestone rewards
            $milestones = $this->loyaltyService->checkAndAwardMilestones($customer, 'shipment_count');
            if (!empty($milestones)) {
                Log::info('Milestone rewards awarded', [
                    'customer_id' => $customer->id,
                    'milestones' => $milestones,
                ]);
            }

            // 12. Generate commercial invoice PDF (required for DHL/International shipments)
            $this->commercialInvoiceService->generateInvoice($ship);

            // 13. Submit to carrier synchronously (no queue for shared hosting)
            $this->shipmentSubmissionService->submit($ship);

            DB::commit();

            // 14. Send notification to customer about successful shipment creation
            // This is non-blocking - if email fails, checkout still succeeds
            try {
                $customer->notify(new ShipmentCreatedNotification($ship));
            } catch (\Exception $emailException) {
                // Log email failure but don't fail checkout
                Log::warning('CheckoutService: Failed to send notification email', [
                    'customer_id' => $customer->id,
                    'ship_id' => $ship->id,
                    'error' => $emailException->getMessage(),
                ]);
            }

            return CheckoutResult::success($ship, $transaction, $stripeCharge->id);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log full error details for debugging
            Log::error('CheckoutService: Checkout failed', [
                'customer_id' => $customer->id,
                'ship_id' => $request->shipId,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Sanitize error message - don't expose Stripe internals or sensitive details
            $userMessage = $this->sanitizeErrorMessage($e->getMessage());
            return CheckoutResult::failure($userMessage);
        }
    }

    /**
     * Verify client-submitted price matches server-calculated price
     * This prevents price manipulation attacks where a user modifies the frontend
     * 
     * @throws \Exception if price mismatch detected
     */
    private function verifyAndCalculatePrice(Ship $ship, CheckoutRequest $request): float
    {
        // Calculate addon charges
        $addonCharges = 0.0;
        if ($request->hasAddons()) {
            $addonCharges = $this->calculateAddonCharges(
                $request->selectedAddonIds,
                $request->declaredValue ?? $ship->total_price ?? 0
            );
        }

        // Get shipping rate from carrier service
        $shippingRate = 0.0;
        if ($request->carrierServiceId || $request->internationalShippingOptionId) {
            try {
                // Resolve destination from customer address
                $destination = $this->resolveDestinationFromAddress($request->customerAddressId);

                // Get packages from ship
                $packageIds = $ship->packages->pluck('id')->toArray();

                if (!empty($packageIds) && !empty($destination)) {
                    $rates = $this->shippingRateService->getRatesForPackages($packageIds, $destination);

                    // Find the selected carrier's rate
                    $carrierId = $request->getEffectiveCarrierId();
                    foreach ($rates as $carrierKey => $carrierData) {
                        if (isset($carrierData['rates'])) {
                            foreach ($carrierData['rates'] as $rate) {
                                if (($rate['id'] ?? null) == $carrierId || ($rate['carrier_service_id'] ?? null) == $request->carrierServiceId) {
                                    $shippingRate = $rate['price'] ?? 0;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('CheckoutService: Could not verify shipping rate, using client rate', [
                    'ship_id' => $ship->id,
                    'error' => $e->getMessage(),
                ]);
                // Fall back to client-submitted rate if we can't verify
                $shippingRate = $request->estimatedShippingCharges - self::BASE_HANDLING_FEE - $addonCharges + $request->loyaltyDiscount;
            }
        }

        // Calculate expected total (subtract both loyalty discount and coupon discount)
        $expectedTotal = self::BASE_HANDLING_FEE + $shippingRate + $addonCharges - $request->loyaltyDiscount;
        
        // Subtract coupon discount if applied
        if ($request->couponDiscount && $request->couponDiscount > 0) {
            $expectedTotal -= $request->couponDiscount;
        }
        
        $expectedTotal = max(0.01, $expectedTotal); // Minimum charge

        // Compare with client-submitted price
        $clientPrice = $request->estimatedShippingCharges;
        $difference = abs($expectedTotal - $clientPrice);

        if ($difference > self::PRICE_TOLERANCE) {
            Log::warning('CheckoutService: Price mismatch detected', [
                'ship_id' => $ship->id,
                'client_price' => $clientPrice,
                'server_price' => $expectedTotal,
                'difference' => $difference,
                'coupon_code' => $request->couponCode,
                'coupon_discount' => $request->couponDiscount,
                'loyalty_discount' => $request->loyaltyDiscount,
                'base_handling_fee' => self::BASE_HANDLING_FEE,
                'shipping_rate' => $shippingRate,
                'addon_charges' => $addonCharges,
            ]);

            // If client submitted a LOWER price, reject it (potential manipulation)
            if ($clientPrice < $expectedTotal - self::PRICE_TOLERANCE) {
                throw new \Exception('Price has changed. Please refresh the page and try again.');
            }

            // If client submitted a HIGHER price, use our calculated (lower) price
            // This protects the customer from overpaying
        }

        // Return the lower of the two prices (protects customer)
        return min($expectedTotal, $clientPrice);
    }

    /**
     * Resolve destination address from customer address ID
     */
    private function resolveDestinationFromAddress(int $addressId): array
    {
        $address = CustomerAddress::find($addressId);

        if (!$address) {
            return [];
        }

        return [
            'street1' => $address->address_line_1 ?? '',
            'city' => $address->city ?? '',
            'state' => $address->state ?? '',
            'zip' => $address->postal_code ?? '',
            'country' => $address->country_code ?? $address->country ?? 'US',
        ];
    }

    /**
     * Sanitize error messages to hide sensitive information
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // List of patterns that indicate sensitive information
        $sensitivePatterns = [
            '/api_key/i',
            '/sk_live_/i',
            '/sk_test_/i',
            '/pk_live_/i',
            '/pk_test_/i',
            '/stripe/i',
            '/curl_error/i',
            '/SQL/i',
            '/SQLSTATE/i',
            '/database/i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return 'Payment processing failed. Please try again or contact support.';
            }
        }

        // Known user-friendly messages can pass through
        $allowedMessages = [
            'Invalid payment method selected.',
            'Payment method not properly configured.',
            'Invalid card token.',
            'This payment method was created in test mode.',
            'Invalid payment amount.',
            'Price has changed.',
            'Shipment not found.',
            'Unauthorized access to shipment.',
        ];

        foreach ($allowedMessages as $allowed) {
            if (str_contains($message, $allowed)) {
                return $message;
            }
        }

        // Check for email-related errors - these should be more specific
        if (preg_match('/blacklisted|550|SMTP|mail/i', $message)) {
            // Email errors shouldn't fail checkout, but if they do, show a friendly message
            return 'Checkout completed successfully, but we encountered an issue sending your confirmation email. Please check your shipment status in your account.';
        }

        // For any other error, return generic message
        if (strlen($message) > 100 || str_contains($message, 'Exception')) {
            return 'An error occurred during checkout. Please try again.';
        }

        return $message;
    }

    /**
     * Update shipment with checkout details including carrier service and addons
     */
    private function updateShipmentDetails(Ship $ship, CheckoutRequest $request, float $verifiedPrice): void
    {
        // Calculate addon charges if addons are selected but charges weren't provided
        $addonCharges = $request->addonCharges;
        if ($request->hasAddons() && ($addonCharges === null || $addonCharges === 0)) {
            $addonCharges = $this->calculateAddonCharges(
                $request->selectedAddonIds,
                $request->declaredValue ?? $ship->total_price ?? 0
            );
        }

        $this->shipRepository->update($ship, [
            // Legacy fields
            'international_shipping_option_id' => json_encode($request->internationalShippingOptionId),
            'packing_option_id' => json_encode($request->packingOptionIds),
            'shipping_preference_option_id' => json_encode($request->shippingPreferenceOptionIds),
            'estimated_shipping_charges' => $verifiedPrice, // Use verified price, not client-submitted
            'customer_address_id' => $request->customerAddressId,

            // New consolidated carrier service fields
            'carrier_service_id' => $request->carrierServiceId,
            'selected_addon_ids' => !empty($request->selectedAddonIds) ? $request->selectedAddonIds : null,
            'addon_charges' => $addonCharges,
            'declared_value' => $request->declaredValue,
            'declared_value_currency' => $request->declaredValueCurrency,
            // Valid ENUM values: live_api, cached, fallback, manual
            'rate_source' => $request->carrierServiceId ? 'cached' : 'fallback',
        ]);
    }

    /**
     * Calculate total addon charges based on selected addon IDs
     */
    private function calculateAddonCharges(array $addonIds, float $declaredValue): float
    {
        if (empty($addonIds)) {
            return 0.0;
        }

        $addons = CarrierAddon::whereIn('id', $addonIds)->get();
        $total = 0.0;

        foreach ($addons as $addon) {
            $total += $addon->calculatePrice($declaredValue);
        }

        return round($total, 2);
    }

    /**
     * Validate payment method belongs to customer and is usable
     * 
     * @throws \Exception if payment method is invalid
     */
    private function validatePaymentMethod(Customer $customer, int $cardId): UserCard
    {
        $card = $this->paymentMethodRepository->findById($cardId);

        if (!$card || $card->customer_id !== $customer->id) {
            throw new \Exception('Invalid payment method selected.');
        }

        if (empty($customer->stripe_id)) {
            throw new \Exception('Payment method not properly configured. Please add a payment method first.');
        }

        $cardStripeId = $card->card_id;
        if (empty($cardStripeId)) {
            throw new \Exception('Invalid payment method. Please re-add your card in account settings.');
        }

        // Detect test/fake card IDs
        if (str_starts_with($cardStripeId, 'tok_test_') || str_starts_with($cardStripeId, 'card_test_')) {
            throw new \Exception('This payment method was created in test mode. Please remove this card and add a new one.');
        }

        // Tokens cannot be used directly on a charge
        if (str_starts_with($cardStripeId, 'tok_') && !str_starts_with($cardStripeId, 'tok_test_')) {
            throw new \Exception('Invalid card token. Please re-add your payment method.');
        }

        return $card;
    }

    /**
     * Charge the customer via Stripe PaymentIntent
     * 
     * Uses PaymentIntent API to support both card and PayPal payments.
     * For saved cards, the payment is confirmed immediately.
     * 
     * @throws \Exception if charge fails
     */
    private function chargeCustomer(Customer $customer, UserCard $card, float $amount, Ship $ship): object
    {
        $amountInCents = (int) round($amount * 100);

        Log::info('CheckoutService: Processing payment via PaymentIntent', [
            'amount' => $amount,
            'amount_cents' => $amountInCents,
            'customer_id' => $customer->id,
            'ship_id' => $ship->id,
            'payment_method' => $card->card_id,
        ]);

        if ($amountInCents <= 0) {
            throw new \Exception('Invalid payment amount. Amount must be greater than zero.');
        }

        // Create and confirm PaymentIntent in one step for saved cards
        $paymentIntent = $this->stripeClient->createPaymentIntent([
            'amount' => $amountInCents,
            'currency' => 'usd',
            'customer' => $customer->stripe_id,
            'payment_method' => $card->card_id,
            'confirm' => true,  // Immediately confirm for saved payment methods
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',  // For saved cards, no redirect needed
            ],
            'receipt_email' => $customer->email,
            'description' => "Payment by {$customer->name} to create shipment.",
            'metadata' => [
                'customer_id' => $customer->id,
                'ship_id' => $ship->id,
                'order_ref' => uniqid('ship_'),
            ],
        ]);

        if (isset($paymentIntent['error'])) {
            Log::error('CheckoutService: Stripe PaymentIntent failed', [
                'error' => $paymentIntent['error'],
                'customer_id' => $customer->id,
            ]);
            throw new \Exception('Payment failed: ' . $paymentIntent['error']);
        }

        // Check if payment succeeded
        if ($paymentIntent->status !== 'succeeded') {
            Log::warning('CheckoutService: PaymentIntent not succeeded', [
                'status' => $paymentIntent->status,
                'customer_id' => $customer->id,
            ]);
            throw new \Exception('Payment was not successful. Status: ' . $paymentIntent->status);
        }

        return $paymentIntent;
    }

    /**
     * Record the transaction in the database
     * 
     * Handles both PaymentIntent (new) and Charge (legacy) response objects.
     */
    private function recordTransaction(Customer $customer, object $paymentResponse): Transaction
    {
        // Determine if this is a PaymentIntent or Charge
        $isPaymentIntent = isset($paymentResponse->object) && $paymentResponse->object === 'payment_intent';

        // Get status - PaymentIntent uses 'status', Charge uses 'paid'
        $isSuccessful = $isPaymentIntent
            ? ($paymentResponse->status === 'succeeded')
            : ($paymentResponse->paid ?? false);

        // Get the last 4 digits of the payment method
        $last4 = 'N/A';
        if ($isPaymentIntent && isset($paymentResponse->payment_method_types)) {
            // For PaymentIntent, we store the payment method type in card field
            $paymentMethodType = $paymentResponse->payment_method_types[0] ?? 'unknown';
            $last4 = ucfirst($paymentMethodType); // e.g., "Card" or "Paypal"

            // Try to get actual last4 from latest_charge if available
            if (isset($paymentResponse->latest_charge) && is_string($paymentResponse->latest_charge)) {
                // We'd need to retrieve the charge to get last4, but for now use payment method type
            }
        } elseif (isset($paymentResponse->source->last4)) {
            // Legacy Charge response
            $last4 = $paymentResponse->source->last4;
        }

        return $this->transactionRepository->create([
            'customer_id' => $customer->id,
            'status' => $isSuccessful ? Transaction::STATUS_SUCCESS : Transaction::STATUS_CANCELED,
            'transaction_id' => $paymentResponse->id,
            'description' => $paymentResponse->description ?? 'Payment processed',
            'amount' => $paymentResponse->amount / 100,
            'card' => $last4,
            'transaction_date' => Carbon::createFromTimestamp($paymentResponse->created)->toDateTimeString(),
        ]);
    }

    /**
     * Record coupon usage
     */
    private function recordCouponUsage(
        Customer $customer,
        Transaction $transaction,
        string $couponCode,
        float $discountAmount,
        float $orderAmount
    ): bool {
        try {
            // Find the coupon
            $coupon = \App\Models\Coupon::where('code', $couponCode)->first();
            
            if (!$coupon) {
                Log::warning('CheckoutService: Coupon not found for usage recording', [
                    'customer_id' => $customer->id,
                    'transaction_id' => $transaction->id,
                    'coupon_code' => $couponCode,
                ]);
                return false;
            }

            // Record the coupon usage
            $recorded = $this->couponService->recordCouponUsage(
                $coupon,
                $customer,
                $transaction,
                $discountAmount,
                $orderAmount
            );

            if (!$recorded) {
                Log::warning('CheckoutService: Coupon usage recording failed', [
                    'customer_id' => $customer->id,
                    'transaction_id' => $transaction->id,
                    'coupon_code' => $couponCode,
                    'coupon_id' => $coupon->id,
                ]);
            }

            return $recorded;
        } catch (\Exception $e) {
            Log::error('CheckoutService: Exception while recording coupon usage', [
                'customer_id' => $customer->id,
                'transaction_id' => $transaction->id,
                'coupon_code' => $couponCode,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Apply loyalty point discount
     */
    private function applyLoyaltyDiscount(
        Customer $customer,
        Transaction $transaction,
        int $pointsUsed,
        float $discountAmount
    ): bool {
        $redeemed = $this->loyaltyService->redeemPoints(
            $customer,
            $transaction,
            $pointsUsed,
            $discountAmount
        );

        if (!$redeemed) {
            Log::warning('CheckoutService: Loyalty redemption failed', [
                'customer_id' => $customer->id,
                'points' => $pointsUsed,
                'discount' => $discountAmount,
            ]);
        }

        return $redeemed;
    }

    /**
     * Update all package statuses to consolidate
     */
    private function updatePackageStatuses(Ship $ship): void
    {
        foreach ($ship->packages as $package) {
            $package->status = PackageStatus::CONSOLIDATE;
            $package->save();
        }
    }
}
