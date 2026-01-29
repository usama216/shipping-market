<?php

namespace App\Helpers;

/**
 * Carrier submission status constants
 * Tracks the status of shipment submission to carrier APIs
 */
class CarrierStatus
{
    const PENDING = 'pending';
    const SUBMITTED = 'submitted';
    const FAILED = 'failed';
    const LABEL_PRINTED = 'label_printed';

    /**
     * Get all status values
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::SUBMITTED,
            self::FAILED,
            self::LABEL_PRINTED,
        ];
    }

    /**
     * Get status labels for UI
     */
    public static function labels(): array
    {
        return [
            self::PENDING => 'Pending',
            self::SUBMITTED => 'Submitted',
            self::FAILED => 'Failed',
            self::LABEL_PRINTED => 'Label Printed',
        ];
    }

    /**
     * Get label for a specific status
     */
    public static function label(string $status): string
    {
        return self::labels()[$status] ?? ucfirst($status);
    }

    /**
     * Get statuses for validation rules
     */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::all());
    }
}
