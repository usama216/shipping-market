<?php
namespace App\Payments;

use Stripe\Charge;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\SubscriptionSchedule;
use Stripe\PaymentMethod;

class Stripe
{
    /**
     * @var StripeClient
     */
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * To charge a credit card, you need to create a new charge object. If your API key is in test mode,
     * the supplied card won't actually be charged, though everything else will occur as if in live mode.
     * (Stripe will assume that the charge would have completed successfully).
     *
     * @param array $attributes
     * @return Charge|array
     */
    public function createCharge(array $attributes): Charge|array
    {
        try {
            return $this->stripe->charges->create($attributes);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves the details of a charge that has been previously created.
     * Supply the unique charge ID that was returned from a previous request, and Stripe will return the corresponding charge information.
     * The same information is returned when creating or refunding the charge.
     *
     * @param string $chargeId
     * @return Charge|array
     */
    public function getChargeById(string $chargeId): Charge|array
    {
        try {
            return $this->stripe->charges->retrieve($chargeId);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Creates a new customer object.
     *
     * @param array $attributes array with product data e.g. ['account_balance' => '15.5' , 'email' => 'email@mail.com']
     * @return Customer|array
     */
    public function createCustomer(array $attributes): Customer|array
    {
        try {
            return $this->stripe->customers->create($attributes);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Creates a new subscription on an existing customer. Each customer can have up to 500 active or scheduled subscriptions.
     *
     * @param array $attributes
     * @return Subscription|array
     */
    public function createSubscription(array $attributes): Subscription|array
    {
        try {
            return $this->stripe->subscriptions->create($attributes);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Updates an existing subscription to match the specified parameters. When changing prices or quantities, we optionally prorate the price we charge next month to make up for any price changes.
     *
     * @param string $subscriptionId
     * @param array $attributes
     * @return Subscription|array
     */
    public function updateSubscription(string $subscriptionId, array $attributes): Subscription|array
    {
        try {
            return $this->stripe->subscriptions->update($subscriptionId, $attributes);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * A subscription schedule allows you to create and manage the lifecycle of a subscription by predefining expected changes.
     *
     * @param array $attributes
     * @return SubscriptionSchedule|array
     */
    public function createSubscriptionSchedule(array $attributes): SubscriptionSchedule|array
    {
        try {
            return $this->stripe->subscriptionSchedules->create($attributes);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves the details of an existing subscription schedule.
     * You only need to supply the unique subscription schedule identifier that was returned upon subscription schedule creation.
     *
     * @param string $subscriptionScheduleId
     * @return SubscriptionSchedule|array
     */
    public function getSubscriptionScheduleById(string $subscriptionScheduleId): SubscriptionSchedule|array
    {
        try {
            return $this->stripe->subscriptionSchedules->retrieve($subscriptionScheduleId);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $subscriptionId
     * @param array $options
     * @return Subscription|array
     */
    public function cancelSubscription(string $subscriptionId, array $options): Subscription|array
    {
        try {
            return $this->stripe->subscriptions->cancel($subscriptionId, $options);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $transactionId
     * @return Refund|array
     */
    public function refund(string $transactionId): Refund|array
    {
        try {
            return $this->stripe->refunds->create(['charge' => $transactionId]);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieves the invoice with the given ID.
     *
     * @param string $invoiceId
     * @return Invoice|string
     */
    public function getInvoiceById(string $invoiceId): Invoice|array
    {
        try {
            return $this->stripe->invoices->retrieve($invoiceId);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $paymentMethodID
     * @param string $customerID
     * @return PaymentMethod|array
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId): PaymentMethod|array
    {
        try {
            return $this->stripe->paymentMethods->attach($paymentMethodId, ['customer' => $customerId]);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $paymentMethodId
     * @param string $customerId
     * @return Customer|array
     */
    public function updateCustomerPaymentMethod(string $paymentMethodId, string $customerId): Customer|array
    {
        try {
            return $this->stripe->customers->update($customerId, ['invoice_settings' => ['default_payment_method' => $paymentMethodId]]);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * @param $startingAfter
     * @return Collection|array
     */
    public function getCharges($startingAfter = null): Collection|array
    {
        try {
            $params = [
                'limit' => 100
            ];

            if (!empty($startingAfter)) {
                $params['starting_after'] = $startingAfter;
            }

            return $this->stripe->charges->all($params);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    public function searchCustomers($query)
    {
        return $this->stripe->customers->search([
            'query' => "email:'{$query}'"
        ]);
    }

    //  return active subscriptions
    public function listSubscriptions($customerId)
    {
        return $this->stripe->subscriptions->all(['customer' => $customerId]);
    }

    public function createSource($user, $cardId)
    {
        return $this->stripe->customers->createSource(
            $user->stripe_id,
            ['source' => $cardId]
        );
    }

    public function retrieveSource(string $customerId, string $sourceId)
    {
        return $this->stripe->customers->retrieveSource($customerId, $sourceId);
    }

    /**
     * Retrieve a payment method by ID
     *
     * @param string $paymentMethodId
     * @return PaymentMethod|array
     */
    public function retrievePaymentMethod(string $paymentMethodId): PaymentMethod|array
    {
        try {
            return $this->stripe->paymentMethods->retrieve($paymentMethodId);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a customer with a payment method attached
     *
     * @param array $attributes
     * @return Customer|array
     */
    public function createCustomerWithPaymentMethod(array $attributes): Customer|array
    {
        try {
            return $this->stripe->customers->create($attributes);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Set a payment method as the default for a customer
     *
     * @param string $customerId
     * @param string $paymentMethodId
     * @return Customer|array
     */
    public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId): Customer|array
    {
        try {
            return $this->stripe->customers->update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId
                ]
            ]);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    // ==========================================
    // Payment Intent Methods (for PayPal support)
    // ==========================================

    /**
     * Create a PaymentIntent for card or PayPal payments
     *
     * @param array $attributes Payment intent attributes
     * @return PaymentIntent|array
     */
    public function createPaymentIntent(array $attributes): PaymentIntent|array
    {
        try {
            return $this->stripe->paymentIntents->create($attributes);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirm a PaymentIntent (used after redirect flows like PayPal)
     *
     * @param string $paymentIntentId
     * @param array $options Optional confirmation options
     * @return PaymentIntent|array
     */
    public function confirmPaymentIntent(string $paymentIntentId, array $options = []): PaymentIntent|array
    {
        try {
            return $this->stripe->paymentIntents->confirm($paymentIntentId, $options);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve a PaymentIntent by ID
     *
     * @param string $paymentIntentId
     * @return PaymentIntent|array
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent|array
    {
        try {
            return $this->stripe->paymentIntents->retrieve($paymentIntentId);
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

}
