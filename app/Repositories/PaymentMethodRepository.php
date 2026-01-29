<?php

namespace App\Repositories;

use App\Interfaces\PaymentMethodInterface;
use App\Models\UserCard;

/**
 * PaymentMethodRepository
 * 
 * Handles payment method (card) operations.
 * Uses customer_id for customer card operations.
 */
class PaymentMethodRepository implements PaymentMethodInterface
{
    protected $userCard;

    public function __construct(UserCard $userCard)
    {
        $this->userCard = $userCard;
    }

    /**
     * Get cards by customer ID
     */
    public function getCardsByCustomer($customerId)
    {
        return $this->userCard->where('customer_id', $customerId)->get();
    }

    /**
     * @deprecated Use getCardsByCustomer instead
     */
    public function getCardsByUser($userId)
    {
        return $this->getCardsByCustomer($userId);
    }

    public function storeUserCard($data)
    {
        return $this->userCard->create($data);
    }

    /**
     * Set default card for customer
     */
    public function setDefaultCard($id, $customerId)
    {
        $this->userCard->where('customer_id', $customerId)->update(['is_default' => false]);
        return $this->userCard->where('id', $id)->where('customer_id', $customerId)->update(['is_default' => true]);
    }

    /**
     * Delete card for customer
     */
    public function deleteCard($id, $customerId)
    {
        $card = $this->userCard->where('id', $id)->where('customer_id', $customerId)->firstOrFail();
        return $card->delete();
    }

    public function findById($id)
    {
        return $this->userCard->where('id', $id)->firstOrFail();
    }

    public function updateUserCard($data, $id)
    {
        return $this->userCard->where('id', $id)->update($data);
    }
}

