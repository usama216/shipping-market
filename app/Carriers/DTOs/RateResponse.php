<?php

namespace App\Carriers\DTOs;

/**
 * RateResponse DTO - Shipping rate quote from carrier
 * 
 * Includes base charge, surcharges breakdown, and total for transparent pricing
 */
class RateResponse
{
    /**
     * Available Value Added Services from carrier (DHL VAS, FedEx surcharges)
     * Populated dynamically after construction by carrier-specific parsing
     */
    public array $availableVas = [];

    public function __construct(
        public readonly string $serviceType,
        public readonly string $serviceName,
        public readonly float $totalCharge,
        public readonly string $currency = 'USD',
        public readonly ?string $estimatedDelivery = null,
        public readonly ?int $transitDays = null,
        public readonly ?float $baseCharge = null,
        public readonly ?float $surcharges = null,
        public readonly ?float $taxes = null,
        public readonly array $surchargeBreakdown = [],
        public readonly array $breakdown = [],
        public readonly array $rawResponse = [],
    ) {
    }

    /**
     * Create from FedEx rate response
     * Parses surcharge breakdown from shipmentRateDetail
     */
    public static function fromFedEx(array $rateDetail): self
    {
        $ratedShipment = $rateDetail['ratedShipmentDetails'][0] ?? [];
        $shipmentRate = $ratedShipment['shipmentRateDetail'] ?? [];
        $netCharge = $ratedShipment['totalNetCharge'] ?? 0;

        // Parse surcharges from FedEx response
        $surchargeBreakdown = self::parseFedExSurcharges($shipmentRate['surcharges'] ?? []);

        return new self(
            serviceType: $rateDetail['serviceType'] ?? '',
            serviceName: $rateDetail['serviceName'] ?? '',
            totalCharge: (float) $netCharge,
            currency: $ratedShipment['currency'] ?? 'USD',
            estimatedDelivery: $rateDetail['operationalDetail']['deliveryDate'] ?? null,
            transitDays: $rateDetail['operationalDetail']['transitTime'] ?? null,
            baseCharge: (float) ($shipmentRate['totalBaseCharge'] ?? 0),
            surcharges: (float) ($shipmentRate['totalSurcharges'] ?? 0),
            surchargeBreakdown: $surchargeBreakdown,
            rawResponse: $rateDetail,
        );
    }

    /**
     * Parse FedEx surcharges array into standardized format
     */
    private static function parseFedExSurcharges(array $surcharges): array
    {
        return array_map(fn($s) => [
            'type' => $s['type'] ?? 'UNKNOWN',
            'description' => $s['description'] ?? self::getSurchargeDescription($s['type'] ?? ''),
            'amount' => (float) ($s['amount'] ?? 0),
            'addon_code' => self::mapFedExSurchargeToAddon($s['type'] ?? ''),
        ], $surcharges);
    }

    /**
     * Map FedEx surcharge types to our addon codes
     */
    private static function mapFedExSurchargeToAddon(string $fedexType): ?string
    {
        return match ($fedexType) {
            'SIGNATURE_OPTION', 'ADULT_SIGNATURE', 'DIRECT_SIGNATURE', 'INDIRECT_SIGNATURE' => 'signature_required',
            'SPECIAL_HANDLING', 'ADDITIONAL_HANDLING' => 'extra_handling',
            'SATURDAY_DELIVERY', 'SATURDAY_PICKUP' => 'saturday_delivery',
            'HOLD_AT_LOCATION' => 'hold_at_location',
            'DANGEROUS_GOODS', 'HAZARDOUS_MATERIALS' => 'dangerous_goods',
            'INSURANCE', 'DECLARED_VALUE' => 'insurance',
            'DRY_ICE' => 'dry_ice',
            'PRIORITY_ALERT', 'PRIORITY_ALERT_PLUS' => 'priority_handling',
            'OVERSIZE', 'OVERSIZE_CHARGE' => 'additional_handling',
            'FUEL' => 'fuel_surcharge',
            'RESIDENTIAL_DELIVERY' => 'residential_delivery',
            default => null,
        };
    }

    /**
     * Get human-readable surcharge description
     */
    private static function getSurchargeDescription(string $type): string
    {
        return match ($type) {
            'FUEL' => 'Fuel Surcharge',
            'RESIDENTIAL_DELIVERY' => 'Residential Delivery',
            'SIGNATURE_OPTION', 'DIRECT_SIGNATURE' => 'Signature Required',
            'ADULT_SIGNATURE' => 'Adult Signature Required',
            'SPECIAL_HANDLING', 'ADDITIONAL_HANDLING' => 'Extra Handling',
            'SATURDAY_DELIVERY' => 'Saturday Delivery',
            'HOLD_AT_LOCATION' => 'Hold at Location',
            'DANGEROUS_GOODS', 'HAZARDOUS_MATERIALS' => 'Dangerous Goods',
            default => ucwords(str_replace('_', ' ', strtolower($type))),
        };
    }

    /**
     * Create from DHL rate response
     */
    public static function fromDHL(array $product): self
    {
        $price = $product['totalPrice'][0] ?? [];
        
        // Extract breakdown from DHL response
        $breakdown = [];
        $surchargeBreakdown = [];
        
        // DHL may include breakdown in totalPrice array or separate breakdown array
        if (isset($price['breakdown'])) {
            $breakdown = $price['breakdown'];
        }
        
        // Extract base charge if available
        $baseCharge = null;
        if (isset($price['basePrice'])) {
            $baseCharge = (float) $price['basePrice'];
        } elseif (isset($product['totalPrice'][0]['basePrice'])) {
            $baseCharge = (float) $product['totalPrice'][0]['basePrice'];
        }
        
        // Extract surcharges if available
        $surcharges = null;
        if (isset($price['totalSurcharges'])) {
            $surcharges = (float) $price['totalSurcharges'];
        } elseif (isset($product['totalPrice'][0]['totalSurcharges'])) {
            $surcharges = (float) $product['totalPrice'][0]['totalSurcharges'];
        }
        
        // Parse surcharges from breakdown if available
        if (isset($price['breakdown']) && is_array($price['breakdown'])) {
            foreach ($price['breakdown'] as $item) {
                if (isset($item['typeCode']) && isset($item['price'])) {
                    $surchargeBreakdown[] = [
                        'type' => $item['typeCode'] ?? '',
                        'description' => $item['name'] ?? $item['typeCode'] ?? '',
                        'amount' => (float) ($item['price'] ?? 0),
                    ];
                }
            }
        }

        return new self(
            serviceType: $product['productCode'] ?? '',
            serviceName: $product['productName'] ?? '',
            totalCharge: (float) ($price['price'] ?? 0),
            currency: $price['priceCurrency'] ?? 'USD',
            estimatedDelivery: $product['deliveryCapabilities']['estimatedDeliveryDateAndTime'] ?? null,
            transitDays: $product['deliveryCapabilities']['totalTransitDays'] ?? null,
            baseCharge: $baseCharge,
            surcharges: $surcharges,
            surchargeBreakdown: $surchargeBreakdown,
            breakdown: $breakdown,
            rawResponse: $product,
        );
    }

    /**
     * Create from UPS rate response
     * Parses surcharge breakdown from RatedShipment
     */
    public static function fromUPS(array $ratedShipment): self
    {
        $service = $ratedShipment['Service'] ?? [];
        $serviceCode = $service['Code'] ?? '';

        // UPS service code to name mapping
        $serviceNames = [
            '01' => 'UPS Next Day Air',
            '02' => 'UPS 2nd Day Air',
            '03' => 'UPS Ground',
            '07' => 'UPS Worldwide Express',
            '08' => 'UPS Worldwide Expedited',
            '11' => 'UPS Standard',
            '12' => 'UPS 3 Day Select',
            '13' => 'UPS Next Day Air Saver',
            '14' => 'UPS Next Day Air Early',
            '54' => 'UPS Worldwide Express Plus',
            '59' => 'UPS 2nd Day Air A.M.',
            '65' => 'UPS Worldwide Saver',
            '82' => 'UPS Today Standard',
            '83' => 'UPS Today Dedicated Courier',
            '84' => 'UPS Today Intercity',
            '85' => 'UPS Today Express',
            '86' => 'UPS Today Express Saver',
            '96' => 'UPS Worldwide Express Freight',
        ];

        $totalCharges = $ratedShipment['TotalCharges'] ?? [];
        $baseCharge = $ratedShipment['TransportationCharges'] ?? [];
        $serviceOptions = $ratedShipment['ServiceOptionsCharges'] ?? [];

        // Parse surcharges from itemized charges
        $itemizedCharges = $ratedShipment['ItemizedCharges'] ?? [];
        
        // UPS may return ItemizedCharges as a single object or array
        // Also check if it's nested under a different key
        if (empty($itemizedCharges) && isset($ratedShipment['RatedPackage'])) {
            // Sometimes charges are per package
            $packages = $ratedShipment['RatedPackage'] ?? [];
            if (!isset($packages[0])) {
                $packages = [$packages];
            }
            foreach ($packages as $pkg) {
                if (isset($pkg['ItemizedCharges'])) {
                    $pkgCharges = $pkg['ItemizedCharges'];
                    if (!isset($pkgCharges[0])) {
                        $pkgCharges = [$pkgCharges];
                    }
                    $itemizedCharges = array_merge($itemizedCharges, $pkgCharges);
                }
            }
        }
        
        $surchargeBreakdown = self::parseUPSSurcharges($itemizedCharges);
        
        // Also include ServiceOptionsCharges if present (these are additional surcharges)
        if (!empty($serviceOptions['MonetaryValue']) && (float)$serviceOptions['MonetaryValue'] > 0) {
            $serviceOptionsAmount = (float) $serviceOptions['MonetaryValue'];
            // Check if this amount is already included in itemized charges
            $alreadyIncluded = array_reduce($surchargeBreakdown, fn($sum, $s) => $sum + $s['amount'], 0);
            if ($serviceOptionsAmount > $alreadyIncluded) {
                $surchargeBreakdown[] = [
                    'type' => 'SERVICE_OPTIONS',
                    'description' => 'Service Options',
                    'amount' => $serviceOptionsAmount - $alreadyIncluded,
                    'addon_code' => null,
                ];
            }
        }
        
        $totalSurcharges = array_reduce($surchargeBreakdown, fn($c, $s) => $c + $s['amount'], 0);

        // Get delivery info
        $timeInTransit = $ratedShipment['TimeInTransit'] ?? [];
        $estimatedArrival = $timeInTransit['ServiceSummary']['EstimatedArrival'] ?? [];
        $deliveryDate = $estimatedArrival['Arrival']['Date'] ?? null;
        $deliveryTime = $estimatedArrival['Arrival']['Time'] ?? null;
        $transitDays = isset($timeInTransit['ServiceSummary']['EstimatedArrival']['BusinessDaysInTransit'])
            ? (int) $timeInTransit['ServiceSummary']['EstimatedArrival']['BusinessDaysInTransit']
            : null;

        return new self(
            serviceType: $serviceCode,
            serviceName: $serviceNames[$serviceCode] ?? $service['Description'] ?? 'UPS Service',
            totalCharge: (float) ($totalCharges['MonetaryValue'] ?? 0),
            currency: $totalCharges['CurrencyCode'] ?? 'USD',
            estimatedDelivery: $deliveryDate ? "{$deliveryDate} {$deliveryTime}" : null,
            transitDays: $transitDays,
            baseCharge: (float) ($baseCharge['MonetaryValue'] ?? 0),
            surcharges: $totalSurcharges,
            surchargeBreakdown: $surchargeBreakdown,
            rawResponse: $ratedShipment,
        );
    }

    /**
     * Parse UPS itemized charges into standardized surcharge format
     */
    private static function parseUPSSurcharges(array $itemizedCharges): array
    {
        // Ensure it's an array of charges
        if (isset($itemizedCharges['Code'])) {
            $itemizedCharges = [$itemizedCharges];
        }

        return array_map(fn($charge) => [
            'type' => $charge['Code'] ?? 'UNKNOWN',
            'description' => $charge['SubType'] ?? self::getUPSSurchargeDescription($charge['Code'] ?? ''),
            'amount' => (float) ($charge['MonetaryValue'] ?? 0),
            'addon_code' => self::mapUPSSurchargeToAddon($charge['Code'] ?? ''),
        ], $itemizedCharges);
    }

    /**
     * Map UPS surcharge codes to our addon codes
     */
    private static function mapUPSSurchargeToAddon(string $upsCode): ?string
    {
        return match ($upsCode) {
            '100', '110', '119' => 'signature_required', // Signature options
            '120', '121' => 'extra_handling', // Additional handling
            '200', '201' => 'saturday_delivery', // Saturday
            '270' => 'hold_at_location', // Hold for pickup
            '375', '376' => 'dangerous_goods', // Hazmat
            '300', '301' => 'insurance', // Declared value
            '400' => 'fuel_surcharge', // Fuel
            '210' => 'residential_delivery', // Residential delivery
            default => null,
        };
    }

    /**
     * Get human-readable UPS surcharge description
     */
    private static function getUPSSurchargeDescription(string $code): string
    {
        return match ($code) {
            '100' => 'Signature Required',
            '110' => 'Adult Signature Required',
            '119' => 'Delivery Confirmation',
            '120' => 'Additional Handling',
            '121' => 'Large Package',
            '200' => 'Saturday Delivery',
            '201' => 'Saturday Pickup',
            '270' => 'Hold for Pickup',
            '300' => 'Declared Value',
            '375' => 'Dangerous Goods',
            '400' => 'Fuel Surcharge',
            default => "Surcharge {$code}",
        };
    }

    /**
     * Convert to array for frontend
     */
    public function toArray(): array
    {
        return [
            'service_type' => $this->serviceType,
            'service_name' => $this->serviceName,
            'total_charge' => $this->totalCharge,
            'currency' => $this->currency,
            'estimated_delivery' => $this->estimatedDelivery,
            'transit_days' => $this->transitDays,
            'base_charge' => $this->baseCharge,
            'surcharges' => $this->surcharges,
            'surcharge_breakdown' => $this->surchargeBreakdown,
            'taxes' => $this->taxes,
            'available_vas' => $this->availableVas, // DHL/UPS VAS or FedEx surcharges
        ];
    }
}

