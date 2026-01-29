<?php

namespace App\Services\DTOs;

/**
 * RateResult DTO - Result of shipping rate lookup with source tracking
 */
class RateResult
{
    public const SOURCE_API = 'api';
    public const SOURCE_CACHE = 'cache';
    public const SOURCE_DATABASE = 'database';

    public function __construct(
        public readonly float $price,
        public readonly string $source,
        public readonly ?string $serviceType = null,
        public readonly ?string $serviceName = null,
        public readonly ?int $transitDays = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly ?string $carrier = null,
        public readonly ?string $error = null,
    ) {
    }

    /**
     * Create a successful API rate result
     */
    public static function fromApi(
        float $price,
        string $serviceType,
        ?string $serviceName = null,
        ?int $transitDays = null,
        ?string $estimatedDelivery = null,
        ?string $carrier = null
    ): self {
        return new self(
            price: $price,
            source: self::SOURCE_API,
            serviceType: $serviceType,
            serviceName: $serviceName,
            transitDays: $transitDays,
            estimatedDelivery: $estimatedDelivery,
            carrier: $carrier,
        );
    }

    /**
     * Create a cached rate result
     */
    public static function fromCache(float $price, ?string $carrier = null): self
    {
        return new self(
            price: $price,
            source: self::SOURCE_CACHE,
            carrier: $carrier,
        );
    }

    /**
     * Create a database fallback result
     */
    public static function fromDatabase(float $price, ?string $carrier = null): self
    {
        return new self(
            price: $price,
            source: self::SOURCE_DATABASE,
            carrier: $carrier,
        );
    }

    /**
     * Create a failed result (fallback with error)
     */
    public static function failed(float $fallbackPrice, string $error, ?string $carrier = null): self
    {
        return new self(
            price: $fallbackPrice,
            source: self::SOURCE_DATABASE,
            carrier: $carrier,
            error: $error,
        );
    }

    /**
     * Check if this rate came from a live API
     */
    public function isLiveRate(): bool
    {
        return $this->source === self::SOURCE_API;
    }

    /**
     * Convert to array for JSON response
     */
    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'source' => $this->source,
            'service_type' => $this->serviceType,
            'service_name' => $this->serviceName,
            'transit_days' => $this->transitDays,
            'estimated_delivery' => $this->estimatedDelivery,
            'carrier' => $this->carrier,
            'is_live_rate' => $this->isLiveRate(),
        ];
    }
}
