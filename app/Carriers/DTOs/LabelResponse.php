<?php

namespace App\Carriers\DTOs;

/**
 * LabelResponse DTO - Shipping label data
 */
class LabelResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $labelData = null, // Base64 encoded
        public readonly ?string $labelUrl = null,
        public readonly string $format = 'PDF', // PDF, PNG, ZPL
        public readonly ?string $trackingNumber = null,
        public readonly ?string $errorMessage = null,
        public readonly array $rawResponse = [],
    ) {
    }

    /**
     * Create success response
     */
    public static function success(
        string $labelData,
        string $format = 'PDF',
        ?string $labelUrl = null,
        ?string $trackingNumber = null,
    ): self {
        return new self(
            success: true,
            labelData: $labelData,
            labelUrl: $labelUrl,
            format: $format,
            trackingNumber: $trackingNumber,
        );
    }

    /**
     * Create failure response
     */
    public static function failure(string $message, array $rawResponse = []): self
    {
        return new self(
            success: false,
            errorMessage: $message,
            rawResponse: $rawResponse,
        );
    }

    /**
     * Get label as binary data (decoded from base64)
     */
    public function getLabelBinary(): ?string
    {
        if (!$this->labelData) {
            return null;
        }

        return base64_decode($this->labelData);
    }

    /**
     * Get appropriate content type for download
     */
    public function getContentType(): string
    {
        return match ($this->format) {
            'PDF' => 'application/pdf',
            'PNG' => 'image/png',
            'ZPL' => 'application/zpl',
            default => 'application/octet-stream',
        };
    }
}
