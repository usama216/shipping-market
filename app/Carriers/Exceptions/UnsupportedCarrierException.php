<?php

namespace App\Carriers\Exceptions;

/**
 * Exception thrown when an unsupported carrier is requested
 */
class UnsupportedCarrierException extends CarrierException
{
    public function __construct(string $carrier)
    {
        parent::__construct(
            message: "Carrier '{$carrier}' is not supported. Available carriers: fedex, dhl, ups",
            code: 400
        );
    }
}
