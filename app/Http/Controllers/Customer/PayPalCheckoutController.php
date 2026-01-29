<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Ship;
use App\Models\Transaction;
use App\Payments\PayPal;
use App\Repositories\ShipRepository;
use App\Repositories\TransactionRepository;
use App\Services\ShipmentSubmissionService;
use App\Notifications\ShipmentCreatedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

/**
 * PayPalCheckoutController
 * 
 * Handles PayPal payment flow via native PayPal REST API v2 (Orders API).
 * PayPal requires a redirect flow where the customer is sent to PayPal,
 * approves the payment, and is redirected back to complete the transaction.
 * 
 * This replaces the previous Stripe-based PayPal implementation which is
 * not available in the USA.
 */
class PayPalCheckoutController extends Controller
{
    public function __construct(
        private PayPal $paypal,
        private ShipRepository $shipRepository,
        private TransactionRepository $transactionRepository,
        private ShipmentSubmissionService $shipmentSubmissionService
    ) {
    }

    /**
     * Create a PayPal Order and return the redirect URL.
     * 
     * This is called when the customer selects PayPal as payment method.
     */
    public function initiatePayPalCheckout(Request $request)
    {
        $request->validate([
            'ship_id' => 'required|integer|exists:ships,id',
            'amount' => 'required|numeric|min:0.01',
            'customer_address_id' => 'required|integer',
        ]);

        $customer = Auth::guard('customer')->user();
        $ship = $this->shipRepository->findById($request->ship_id);

        if (!$ship || $ship->customer_id !== $customer->id) {
            return response()->json(['error' => 'Invalid shipment'], 403);
        }

        try {
            // Create PayPal Order
            $order = $this->paypal->createOrder([
                'amount' => (float) $request->amount,
                'description' => "Payment by {$customer->name} for shipment #{$ship->id}",
                'reference_id' => "ship_{$ship->id}",
                'return_url' => route('customer.checkout.paypal.return', [
                    'ship_id' => $ship->id,
                ]),
                'cancel_url' => route('customer.shipment.index', ['ship' => Crypt::encrypt($ship->id)]),
            ]);

            // Store the PayPal Order ID on the ship for later verification
            $ship->pending_payment_intent_id = $order['id']; // Reusing existing field for PayPal order ID
            $ship->save();

            return response()->json([
                'order_id' => $order['id'],
                'approval_url' => $order['approval_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('PayPal checkout initiation failed', [
                'customer_id' => $customer->id,
                'ship_id' => $ship->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to initiate PayPal checkout. Please try again.'
            ], 500);
        }
    }

    /**
     * Handle the return URL after customer completes PayPal payment.
     * 
     * PayPal redirects here after the customer approves the payment.
     */
    public function handlePayPalReturn(Request $request)
    {
        $orderId = $request->query('token'); // PayPal returns 'token' as the order ID
        $shipId = $request->query('ship_id');

        $customer = Auth::guard('customer')->user();

        if (!$orderId || !$shipId) {
            return redirect()->route('customer.shipment.index')
                ->withErrors(['message' => 'Invalid payment return - missing payment information.']);
        }

        try {
            // Find the ship using the payment order ID
            $ship = Ship::where('pending_payment_intent_id', $orderId)
                ->where('customer_id', $customer->id)
                ->where('id', $shipId)
                ->first();

            if (!$ship) {
                throw new \Exception('Shipment not found for this payment.');
            }

            // Capture the PayPal payment
            $capture = $this->paypal->captureOrder($orderId);

            // Check capture status
            if ($capture['status'] === 'COMPLETED') {
                return $this->completePayPalPayment($customer, $ship, $capture);
            } else {
                // Payment was not completed
                return redirect()->route('customer.shipment.index', ['ship' => Crypt::encrypt($ship->id)])
                    ->withErrors(['message' => 'Payment was not completed. Status: ' . $capture['status']]);
            }

        } catch (\Exception $e) {
            Log::error('PayPal return handling failed', [
                'order_id' => $orderId,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            // Try to find the ship to redirect properly
            $ship = Ship::where('id', $shipId)
                ->where('customer_id', $customer->id)
                ->first();

            if ($ship) {
                return redirect()->route('customer.shipment.index', ['ship' => Crypt::encrypt($ship->id)])
                    ->withErrors(['message' => 'Payment verification failed. Please try again.']);
            }

            return redirect()->route('customer.shipment.index')
                ->withErrors(['message' => 'Payment verification failed. Please contact support.']);
        }
    }

    /**
     * Complete the PayPal payment after successful capture.
     */
    private function completePayPalPayment($customer, Ship $ship, array $capture)
    {
        try {
            DB::beginTransaction();

            // Extract payment amount from capture
            $amount = 0;
            if (!empty($capture['purchase_units'])) {
                $payments = $capture['purchase_units'][0]['payments']['captures'] ?? [];
                if (!empty($payments)) {
                    $amount = (float) ($payments[0]['amount']['value'] ?? 0);
                }
            }

            // Record the transaction
            $transaction = $this->transactionRepository->create([
                'customer_id' => $customer->id,
                'status' => Transaction::STATUS_SUCCESS,
                'transaction_id' => $capture['id'],
                'description' => 'PayPal payment for shipment #' . $ship->id,
                'amount' => $amount,
                'card' => 'PayPal',
                'transaction_date' => Carbon::now()->toDateTimeString(),
            ]);

            // Update package statuses
            foreach ($ship->packages as $package) {
                $package->status = \App\Helpers\PackageStatus::CONSOLIDATE;
                $package->save();
            }

            // Mark shipment as paid
            $ship->invoice_status = 'paid';
            $ship->status = ShipmentStatus::PAID;
            $ship->pending_payment_intent_id = null; // Clear the pending order ID
            $ship->estimated_shipping_charges = $amount;
            $ship->save();

            // Submit to carrier synchronously (no queue for shared hosting)
            $this->shipmentSubmissionService->submit($ship);

            DB::commit();

            // Send notification to customer about successful shipment creation
            $customer->notify(new ShipmentCreatedNotification($ship));

            return redirect()->route('customer.shipment.success', ['shipId' => $ship->id])
                ->with('alert', 'Payment successful! Your shipment is being processed.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PayPal payment completion failed', [
                'customer_id' => $customer->id,
                'ship_id' => $ship->id,
                'capture' => $capture,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('customer.shipment.index', ['ship' => Crypt::encrypt($ship->id)])
                ->withErrors(['message' => 'Failed to complete payment. Please contact support.']);
        }
    }
}
