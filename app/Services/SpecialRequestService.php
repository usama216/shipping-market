<?php

namespace App\Services;

use App\Helpers\PackageStatus;
use App\Models\Package;
use App\Models\SpecialRequest;
use App\Models\Customer;
use App\Models\UserCard;
use App\Models\Transaction;
use App\Payments\Stripe;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpecialRequestService
{
    public function __construct(
        private Stripe $stripeClient,
        private TransactionRepository $transactionRepository
    ) {
    }

    /**
     * Process special request for a package
     * 
     * Flow:
     * 1. Charge customer for special request
     * 2. Move package back to "in review" status
     * 3. Request return shipping label if applicable (return_to_sender)
     * 
     * @param Package $package
     * @param array $specialRequestIds
     * @param Customer $customer
     * @param int $cardId Payment method ID
     * @return array
     */
    public function processSpecialRequest(Package $package, array $specialRequestIds, Customer $customer, int $cardId): array
    {
        try {
            DB::beginTransaction();

            // Get special requests
            $specialRequests = SpecialRequest::whereIn('id', $specialRequestIds)->get();
            
            if ($specialRequests->isEmpty()) {
                throw new \Exception('No valid special requests found.');
            }

            // Calculate total cost
            $totalCost = $specialRequests->sum('price');
            
            // Check if any request requires return shipping label
            $requiresReturnLabel = $specialRequests->contains(function ($request) {
                return $request->type === 'return_to_sender';
            });

            // Charge customer if cost > 0
            $transaction = null;
            if ($totalCost > 0) {
                $card = UserCard::where('id', $cardId)
                    ->where('customer_id', $customer->id)
                    ->firstOrFail();

                $transaction = $this->chargeCustomer($customer, $card, $totalCost, $package);
            }

            // Update package: set special requests and move to IN_REVIEW
            $package->selected_addon_ids = $specialRequestIds;
            $package->status = PackageStatus::IN_REVIEW;
            $package->save();

            DB::commit();

            Log::info('Special request processed', [
                'package_id' => $package->id,
                'customer_id' => $customer->id,
                'special_request_ids' => $specialRequestIds,
                'total_cost' => $totalCost,
                'requires_return_label' => $requiresReturnLabel,
                'new_status' => PackageStatus::IN_REVIEW,
                'transaction_id' => $transaction?->id,
            ]);

            return [
                'success' => true,
                'message' => 'Special request processed successfully. Package moved to In Review.',
                'total_cost' => $totalCost,
                'requires_return_label' => $requiresReturnLabel,
                'transaction_id' => $transaction?->id,
                'special_requests' => $specialRequests->map(fn($sr) => [
                    'id' => $sr->id,
                    'title' => $sr->title,
                    'type' => $sr->type,
                    'price' => $sr->price,
                ]),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Special request processing failed', [
                'package_id' => $package->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Charge customer for special request
     */
    private function chargeCustomer(Customer $customer, UserCard $card, float $amount, Package $package): Transaction
    {
        $amountInCents = (int) round($amount * 100);

        if ($amountInCents <= 0) {
            throw new \Exception('Invalid payment amount. Amount must be greater than zero.');
        }

        // Create and confirm PaymentIntent
        $paymentIntent = $this->stripeClient->createPaymentIntent([
            'amount' => $amountInCents,
            'currency' => 'usd',
            'customer' => $customer->stripe_id,
            'payment_method' => $card->card_id,
            'confirm' => true,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'receipt_email' => $customer->email,
            'description' => "Special request charge for package #{$package->id}",
            'metadata' => [
                'customer_id' => $customer->id,
                'package_id' => $package->id,
                'type' => 'special_request',
            ],
        ]);

        if (isset($paymentIntent['error'])) {
            throw new \Exception('Payment failed: ' . $paymentIntent['error']);
        }

        if ($paymentIntent->status !== 'succeeded') {
            throw new \Exception('Payment was not successful. Status: ' . $paymentIntent->status);
        }

        // Record transaction
        return $this->transactionRepository->create([
            'customer_id' => $customer->id,
            'amount' => $amount,
            'type' => 'special_request',
            'status' => 'completed',
            'stripe_payment_intent_id' => $paymentIntent->id,
            'description' => "Special request charge for package #{$package->id}",
        ]);
    }

    /**
     * Get special requests that require return shipping label
     */
    public function requiresReturnLabel(array $specialRequestIds): bool
    {
        $specialRequests = SpecialRequest::whereIn('id', $specialRequestIds)->get();
        return $specialRequests->contains(function ($request) {
            return $request->type === 'return_to_sender';
        });
    }
}

