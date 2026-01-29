<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserCardRequest;
use App\Http\Requests\UpdateUserCardRequest;
use App\Payments\Stripe;
use App\Repositories\PaymentMethodRepository;
use App\Repositories\CustomerRepository;
use App\Traits\CommonTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    use CommonTrait;

    protected $paymentMethodRepository, $customerRepository, $stripe;

    public function __construct(PaymentMethodRepository $paymentMethodRepository, CustomerRepository $customerRepository, Stripe $stripe)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->customerRepository = $customerRepository;
        $this->stripe = $stripe;
    }

    public function paymentMethods()
    {
        $customer = Auth::guard('customer')->user();
        $cards = $this->paymentMethodRepository->getCardsByCustomer($customer->id);
        return Inertia::render('Customers/Profile/EditTabs/PaymentMethod', [
            'publishableKey' => env('STRIPE_KEY'),
            'cards' => $cards,
            'customerAddresses' => $customer->addresses ?? [],
        ]);
    }

    /**
     * Store a new payment method (card) for the authenticated user.
     *
     * This method handles both modern Stripe payment method IDs (from Stripe Elements/Checkout)
     * and legacy Stripe tokens (from Stripe.js). It supports:
     * - Creating new Stripe customers if the user doesn't have one
     * - Attaching payment methods to existing customers
     * - Setting cards as default payment methods
     * - Storing card details in the local database
     * - Supporting multiple cards per user
     *
     * @param StoreUserCardRequest $request The validated request containing card details
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeCard(StoreUserCardRequest $request)
    {
        try {
            DB::beginTransaction();

            // Get the authenticated customer
            $customer = Auth::guard('customer')->user();
            if (!$customer) {
                throw new \Exception('Authenticated customer not found.');
            }

            // Get payment method ID or token from request
            $paymentMethodId = $request->input('payment_method_id');
            $token = $request->input('token');

            if (!$paymentMethodId && !$token) {
                throw new \Exception('Payment method ID or token is required.');
            }

            $stripePaymentMethod = null;
            $cardDetails = [];

            // Handle payment method ID (from Stripe Elements or Checkout)
            if ($paymentMethodId) {
                $stripePaymentMethod = $this->stripe->retrievePaymentMethod($paymentMethodId);

                if (isset($stripePaymentMethod['error'])) {
                    throw new \Exception('Failed to retrieve payment method: ' . $stripePaymentMethod['error']);
                }

                $cardDetails = [
                    'exp_month' => $stripePaymentMethod->card->exp_month,
                    'exp_year' => $stripePaymentMethod->card->exp_year,
                    'brand' => $stripePaymentMethod->card->brand,
                    'last4' => $stripePaymentMethod->card->last4,
                ];
            }
            // Handle token (legacy Stripe.js)
            elseif ($token) {
                if (!isset($token['id']) || !isset($token['card'])) {
                    throw new \Exception('Invalid card token received.');
                }

                $cardDetails = [
                    'exp_month' => data_get($token['card'], 'exp_month'),
                    'exp_year' => data_get($token['card'], 'exp_year'),
                    'brand' => data_get($token['card'], 'brand'),
                    'last4' => data_get($token['card'], 'last4'),
                ];

                $paymentMethodId = $token['id'];
            }

            // Check if customer has Stripe customer ID
            if (empty($customer->stripe_id)) {
                // Create new Stripe customer
                $customerData = [
                    'email' => $customer->email,
                    'name' => $customer->name,
                    'metadata' => ['customer_id' => $customer->id],
                ];

                // If using payment method ID, attach it to the customer
                if ($paymentMethodId && !$token) {
                    $customerData['payment_method'] = $paymentMethodId;
                    $customerData['invoice_settings'] = [
                        'default_payment_method' => $paymentMethodId
                    ];
                }
                // If using token, use source parameter
                elseif ($token) {
                    $customerData['source'] = $token['id'];
                }

                $stripeCustomer = $this->stripe->createCustomerWithPaymentMethod($customerData);

                if (isset($stripeCustomer['error'])) {
                    throw new \Exception('Failed to create Stripe customer: ' . $stripeCustomer['error']);
                }

                // Update customer with Stripe customer ID
                $this->customerRepository->update($customer->id, ['stripe_id' => $stripeCustomer->id]);
                $customer->stripe_id = $stripeCustomer->id;

                // If using token, get the attached source
                if ($token) {
                    if (!empty($stripeCustomer->default_source)) {
                        $stripePaymentMethod = $this->stripe->retrieveSource($stripeCustomer->id, $stripeCustomer->default_source);
                        if (isset($stripePaymentMethod['error'])) {
                            throw new \Exception('Failed to retrieve attached card: ' . $stripePaymentMethod['error']);
                        }
                    } else {
                        throw new \Exception('No default card was attached to the customer.');
                    }
                }
            } else {
                // Customer already has Stripe customer ID, attach payment method
                if ($paymentMethodId && !$token) {
                    $attachedPaymentMethod = $this->stripe->attachPaymentMethod($paymentMethodId, $customer->stripe_id);
                    if (isset($attachedPaymentMethod['error'])) {
                        throw new \Exception('Failed to attach payment method: ' . $attachedPaymentMethod['error']);
                    }
                    $stripePaymentMethod = $attachedPaymentMethod;
                } elseif ($token) {
                    $stripePaymentMethod = $this->stripe->createSource($customer, $token['id']);
                    if (isset($stripePaymentMethod['error'])) {
                        throw new \Exception('Failed to create source: ' . $stripePaymentMethod['error']);
                    }
                }
            }

            // Check if this should be set as default payment method
            $setAsDefault = $request->input('set_as_default', false);
            if ($setAsDefault && $paymentMethodId && !$token) {
                $this->stripe->setDefaultPaymentMethod($customer->stripe_id, $paymentMethodId);
            }

            // Prepare data for database storage
            $data = [
                'customer_id' => $customer->id,
                'card_id' => $stripePaymentMethod->id,
                'exp_month' => $cardDetails['exp_month'],
                'exp_year' => $cardDetails['exp_year'],
                'brand' => $cardDetails['brand'],
                'last4' => $cardDetails['last4'],
                'card_holder_name' => $request->input('card_holder_name'),
                'address_line1' => $request->input('address_line1'),
                'address_line2' => $request->input('address_line2'),
                'country' => $request->input('country'),
                'state' => $request->input('state'),
                'city' => $request->input('city'),
                'postal_code' => $request->input('postal_code'),
                'country_code' => $request->input('country_code'),
                'phone_number' => $request->input('phone_number'),
                'is_default' => $setAsDefault,
            ];

            // If setting as default, unset other cards as default
            if ($setAsDefault) {
                $this->paymentMethodRepository->setDefaultCard($data['card_id'], $customer->id);
            }

            // Store card in database
            $this->paymentMethodRepository->storeUserCard($data);

            DB::commit();

            return Redirect::back()->with('alert', 'Card added successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $customer = Auth::guard('customer')->user();
            \Log::error('Failed to store card: ' . $e->getMessage(), [
                'customer_id' => $customer?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return Redirect::back()->withErrors(['message' => 'Failed to save card: ' . $e->getMessage()]);
        }
    }

    public function setDefault($id)
    {
        try {
            $customer = Auth::guard('customer')->user();
            DB::beginTransaction();
            $this->paymentMethodRepository->setDefaultCard($id, $customer->id);
            DB::commit();
            return Redirect::route('customer.payment.paymentMethods')->with('alert', 'Default card updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => 'Failed to set default card: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $customer = Auth::guard('customer')->user();
            DB::beginTransaction();
            $this->paymentMethodRepository->deleteCard($id, $customer->id);
            DB::commit();
            return Redirect::route('customer.payment.paymentMethods')->with('alert', 'Card deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => 'Failed to destroy card: ' . $e->getMessage()]);
        }
    }
    public function updateCard(UpdateUserCardRequest $request, $id)
    {
        try {
            DB::beginTransaction();
            $this->paymentMethodRepository->updateUserCard($request->validated(), $id);
            DB::commit();
            return Redirect::route('customer.payment.paymentMethods')->with('alert', 'Card updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return Redirect::back()->withErrors(['message' => 'Failed to update card: ' . $e->getMessage()]);
        }
    }
}
