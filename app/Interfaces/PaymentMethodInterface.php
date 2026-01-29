<?php

namespace App\Interfaces;

use Request;

interface PaymentMethodInterface
{
    public function storeUserCard($data);
    public function getCardsByUser($userId);

    public function setDefaultCard($id, $userId);

    public function deleteCard($id, $userId);

    public function findById($id);

    public function updateUserCard($data, $id);

}
