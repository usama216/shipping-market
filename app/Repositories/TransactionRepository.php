<?php

namespace App\Repositories;

use App\Interfaces\TransactionInterface;
use App\Models\Transaction;

class TransactionRepository implements TransactionInterface
{
    protected $transaction;
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function getAllTransaction()
    {
        return $this->transaction->with(['user', 'customer'])->paginate(25);
    }

    public function create($data)
    {
        return $this->transaction->create($data);
    }

    /**
     * Get transactions for customer by customer_id
     * Supports both customer_id and user_id for backward compatibility
     */
    public function getTransactionsByCustomer($customerId)
    {
        return $this->transaction
            ->where(function ($query) use ($customerId) {
                $query->where('customer_id', $customerId)
                    ->orWhere('user_id', $customerId);
            })
            ->with(['user', 'customer'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);
    }

    /**
     * @deprecated Use getTransactionsByCustomer instead
     */
    public function getTransactionById($userId)
    {
        return $this->getTransactionsByCustomer($userId);
    }

    public function findById($transactionId)
    {
        return $this->transaction->findOrFail($transactionId);
    }

    public function update($id, $data)
    {
        return $this->transaction->where('id', $id)->update($data);
    }

    public function sumTotalTransaction()
    {
        return $this->transaction->sum('amount');
    }
}
