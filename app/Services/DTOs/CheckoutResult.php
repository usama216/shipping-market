<?php

namespace App\Services\DTOs;

use App\Models\Ship;
use App\Models\Transaction;

/**
 * Data Transfer Object for checkout result
 * Encapsulates the outcome of a checkout operation
 */
readonly class CheckoutResult
{
    public function __construct(
        public bool $success,
        public ?Ship $ship = null,
        public ?Transaction $transaction = null,
        public ?string $error = null,
        public ?string $stripeChargeId = null,
    ) {
    }

    /**
     * Create successful result
     */
    public static function success(Ship $ship, Transaction $transaction, ?string $stripeChargeId = null): self
    {
        return new self(
            success: true,
            ship: $ship,
            transaction: $transaction,
            error: null,
            stripeChargeId: $stripeChargeId,
        );
    }

    /**
     * Create failed result
     */
    public static function failure(string $error): self
    {
        return new self(
            success: false,
            ship: null,
            transaction: null,
            error: $error,
        );
    }

    /**
     * Get redirect route after checkout
     */
    public function getRedirectRoute(): string
    {
        return $this->success
            ? 'customer.shipment.success'
            : 'customer.shipment.create';
    }
}
