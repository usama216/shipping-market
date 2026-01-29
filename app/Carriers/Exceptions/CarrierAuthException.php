<?php

namespace App\Carriers\Exceptions;

/**
 * Exception thrown when authentication with carrier API fails
 */
class CarrierAuthException extends CarrierException
{
    public function __construct(string $carrier, string $message = '', array $rawResponse = [])
    {
        parent::__construct(
            message: "Authentication failed for {$carrier}: {$message}",
            rawResponse: $rawResponse,
            code: 401
        );
    }
}
