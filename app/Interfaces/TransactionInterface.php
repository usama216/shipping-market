<?php

namespace App\Interfaces;

interface TransactionInterface
{
    public function getAllTransaction();
    public function create($data);

    public function getTransactionById($userId);

    public function findById($transactionId);

    public function update($id, $data);

    public function sumTotalTransaction();
}
