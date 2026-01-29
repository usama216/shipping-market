<?php

namespace App\Carriers\DTOs;

/**
 * DocumentImage DTO - Represents a document image for customs upload
 * 
 * Used with DHL Express API to pre-upload customs documents like:
 * - DCL: Declaration (customs)
 * - INV: Commercial Invoice
 * - COO: Certificate of Origin
 * - AWB: Air Waybill
 */
class DocumentImage
{
    public function __construct(
        public readonly string $content,           // Base64-encoded PDF content
        public readonly string $imageFormat = 'PDF',
        public readonly string $typeCode = 'DCL',  // DCL, INV, COO, AWB
    ) {
    }

    /**
     * Convert to DHL API format
     */
    public function toArray(): array
    {
        return [
            'imageFormat' => $this->imageFormat,
            'content' => $this->content,
            'typeCode' => $this->typeCode,
        ];
    }

    /**
     * Create from a file path
     */
    public static function fromFile(string $filePath, string $typeCode = 'DCL'): self
    {
        $content = base64_encode(file_get_contents($filePath));
        $extension = strtoupper(pathinfo($filePath, PATHINFO_EXTENSION));

        return new self(
            content: $content,
            imageFormat: $extension === 'PDF' ? 'PDF' : $extension,
            typeCode: $typeCode,
        );
    }
}
