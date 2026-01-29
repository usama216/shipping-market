<?php

namespace App\Carriers\DTOs;

use App\Models\Ship;
use App\Models\Package;
use App\Models\Warehouse;
use App\Models\CarrierService;
use App\Models\CarrierAddon;
use App\Services\CommercialInvoiceService;
use App\Carriers\DTOs\DocumentImage;
use Illuminate\Support\Facades\Log;

/**
 * ShipmentRequest DTO - Unified request structure for all carriers
 */
class ShipmentRequest
{
    public function __construct(
        // Sender Information
        public readonly string $senderName,
        public readonly string $senderCompany,
        public readonly string $senderPhone,
        public readonly string $senderEmail,
        public readonly Address $senderAddress,

        // Recipient Information  
        public readonly string $recipientName,
        public readonly string $recipientPhone,
        public readonly string $recipientEmail,
        public readonly Address $recipientAddress,

        // Package Details
        public readonly array $packages, // PackageDetail[]

        // Shipment Options
        public readonly ?string $serviceType = null,  // null = get all available services
        public readonly string $packagingType = 'YOUR_PACKAGING',
        public readonly ?string $shipDate = null,
        public readonly bool $signatureRequired = false,
        public readonly ?float $declaredValue = null,
        public readonly string $currency = 'USD',

        // Customs (International)
        public readonly ?string $dutiesPayor = 'SENDER', // SENDER, RECIPIENT, THIRD_PARTY
        public readonly ?array $commodities = null, // CommodityDetail[]

        // Reference
        public readonly ?string $referenceNumber = null,
        public readonly ?string $poNumber = null,

        // Optional special services to request pricing for (FedEx surcharges)
        public readonly array $requestedServices = [],

        // Recipient Tax ID for customs/export documents
        public readonly ?string $recipientTaxId = null,

        // DHL Export Compliance Fields
        public readonly ?string $usFilingTypeValue = null,       // '30.37(a)', '30.37(y)', or ITN
        public readonly ?string $incoterm = 'DAP',               // DAP, DDU, DDP
        public readonly ?string $invoiceSignatureName = null,    // Authorized signer name
        public readonly ?string $invoiceSignatureTitle = null,   // Title (Mr., Ms., etc.)
        public readonly ?string $exporterId = null,              // EAR99, ECCN codes
        public readonly ?string $exporterCode = null,            // EXPCZ, etc.

        // DHL Advanced Features
        public readonly array $documentImages = [],              // DocumentImage[] for customs documents
        public readonly array $valueAddedServices = [],          // DHL VAS codes (dangerous goods, etc.)
    ) {
    }

    /**
     * Create ShipmentRequest from Ship and Package models
     */
    public static function fromShip(Ship $ship, array $packages): self
    {
        $ship->load('customerAddress', 'user', 'internationalShipping', 'customer');


        // Get warehouse origin address from first package
        $firstPackage = $packages[0] ?? null;
        $warehouse = $firstPackage?->warehouse;

        // Get recipient address
        $recipientAddr = $ship->customerAddress;

        // Build package details
        $packageDetails = [];
        $commodities = [];
        $dangerousGoodsVas = []; // Collect DG items for VAS

        foreach ($packages as $pkg) {
            $pkg->load('items');

            // Aggregate dimensions from items (Package model doesn't have direct l/w/h fields)
            // Find the maximum bounding box dimensions from all items in the package
            $maxLength = $pkg->items->max('length') ?? 0;
            $maxWidth = $pkg->items->max('width') ?? 0;
            $maxHeight = $pkg->items->max('height') ?? 0;

            // Use billable weight (max of physical or volumetric) - this is what carriers charge on
            $packageWeight = (float) ($pkg->billed_weight ?? $pkg->total_weight ?? $pkg->weight ?? 0);

            $packageDetails[] = new PackageDetail(
                weight: $packageWeight > 0 ? $packageWeight : 1.0, // Carrier requires weight > 0
                weightUnit: $pkg->weight_unit ?? 'LB',
                length: (float) $maxLength,
                width: (float) $maxWidth,
                height: (float) $maxHeight,
                dimensionUnit: strtoupper($pkg->dimension_unit ?? 'IN'),
                declaredValue: (float) $pkg->total_value,
            );

            // Build commodities for customs
            foreach ($pkg->items as $item) {
                $commodities[] = new CommodityDetail(
                    description: $item->title,
                    quantity: (int) $item->quantity,
                    unitValue: (float) $item->value_per_unit,
                    totalValue: (float) $item->total_line_value,
                    weight: (float) ($item->weight_per_unit ?? 0) * $item->quantity,
                    weightUnit: $item->weight_unit ?? 'LB',
                    hsCode: $item->hs_code,
                    countryOfOrigin: $item->country_of_origin ?? 'US',
                    material: $item->material,
                );

                // Collect dangerous goods items for VAS
                if ($item->is_dangerous && $item->un_code) {
                    $dgClass = $item->dangerous_goods_class ?? '9'; // Default Class 9 (Lithium batteries)
                    // Map DG class to DHL service code: HE=limited qty, HH=fully regulated
                    $serviceCode = in_array($dgClass, ['1', '7']) ? 'HH' : 'HE'; // Class 1/7 = fully regulated
                    // Map DG class to contentId (DHL codes)
                    $contentId = self::mapDgClassToContentId($dgClass);

                    $dangerousGoodsVas[] = [
                        'serviceCode' => $serviceCode,
                        'dangerousGoods' => [
                            [
                                'contentId' => $contentId,
                                'unCode' => $item->un_code,
                            ],
                        ],
                    ];
                }
            }
        }

        // Build valueAddedServices from collected DG items + customer-selected addons
        $valueAddedServices = !empty($dangerousGoodsVas) ? $dangerousGoodsVas : [];

        // Merge customer-selected addons (insurance, handling, etc.) into VAS
        if (!empty($ship->selected_addon_ids)) {
            $addonVas = self::mapSelectedAddonsToDhlVas($ship->selected_addon_ids, $ship->declared_value ?? 0, $ship->declared_value_currency ?? 'USD');
            $valueAddedServices = array_merge($valueAddedServices, $addonVas);
        }

        // Map shipping option to service type based on destination
        $destinationCountry = $recipientAddr->country_code ?? 'US';

        // New: Use carrier_service_id if available (from new schema)
        $serviceType = null;
        if ($ship->carrier_service_id) {
            $carrierService = $ship->carrierService ?? CarrierService::find($ship->carrier_service_id);
            if ($carrierService) {
                $serviceType = $carrierService->getApiServiceCode();
            }
        }

        // Fallback: Use legacy international_shipping_option_id
        if (!$serviceType && $ship->international_shipping_option_id) {
            $serviceType = self::mapShippingOptionToService(
                $ship->international_shipping_option_id,
                $destinationCountry
            );
        }

        // Ultimate fallback: Auto-select based on route
        if (!$serviceType) {
            $autoSelected = CarrierService::autoSelectForRoute('US', $destinationCountry);
            $serviceType = $autoSelected?->getApiServiceCode() ?? 'FEDEX_INTERNATIONAL_PRIORITY';
        }

        // Get declared value from new field or fallback to total_price
        $declaredValue = $ship->declared_value ?? (float) $ship->total_price;

        // Get recipient tax ID from ship (national_id) or customer's saved tax_id
        $recipientTaxId = $ship->national_id ?? $ship->customer?->tax_id ?? null;

        return new self(
            senderName: $warehouse?->full_name ?? config('carriers.default_sender_name', 'Marketz Warehouse'),
            senderCompany: $warehouse?->company_name ?? config('carriers.default_sender_company', 'Marketz LLC'),
            senderPhone: $warehouse?->phone_number ?? config('carriers.default_sender_phone'),
            senderEmail: config('carriers.default_sender_email', 'shipping@marketz.com'),
            senderAddress: new Address(
                street1: $warehouse?->address ?? config('carriers.default_sender_address'),
                street2: $warehouse?->address_line_2,
                city: $warehouse?->city ?? config('carriers.default_sender_city'),
                state: $warehouse?->state ?? config('carriers.default_sender_state'),
                postalCode: $warehouse?->zip ?? config('carriers.default_sender_zip'),
                countryCode: $warehouse?->country_code ?? 'US',
            ),
            recipientName: $recipientAddr->full_name ?? '',
            recipientPhone: $recipientAddr->phone_number ?? '',
            recipientEmail: $ship->user?->email ?? '',
            recipientAddress: new Address(
                street1: $recipientAddr->address_line_1 ?? '',
                street2: $recipientAddr->address_line_2,
                city: $recipientAddr->city ?? '',
                state: $recipientAddr->state ?? '',
                postalCode: $recipientAddr->postal_code ?? '',
                countryCode: $recipientAddr->country_code ?? 'US',
            ),
            packages: $packageDetails,
            serviceType: $serviceType,
            // FedEx Ground requires YOUR_PACKAGING, Express services allow FEDEX_BOX etc.
            packagingType: self::getCompatiblePackagingType($serviceType, $firstPackage?->package_type),
            shipDate: now()->format('Y-m-d'),
            declaredValue: $declaredValue,
            currency: $ship->declared_value_currency ?? 'USD',
            commodities: $commodities,
            referenceNumber: $ship->tracking_number,
            recipientTaxId: $recipientTaxId,
            // DHL Export Compliance - use Ship fields with sensible defaults
            usFilingTypeValue: $ship->us_filing_type ?? ($declaredValue < 2500 ? '30.37(a)' : null),
            incoterm: $ship->incoterm ?? 'DAP',
            invoiceSignatureName: $ship->invoice_signature_name ?? $warehouse?->full_name ?? config('carriers.default_sender_name', 'Authorized Shipper'),
            invoiceSignatureTitle: $ship->invoice_signature_title ?? 'Mr.',
            exporterId: $ship->exporter_id ?? 'EAR99',
            exporterCode: $ship->exporter_code ?? 'EXPCZ',
            documentImages: self::getDocumentImagesForShip($ship),
            valueAddedServices: $valueAddedServices, // DHL VAS for dangerous goods
        );
    }

    /**
     * Get document images (commercial invoice) for DHL Paperless Trade
     * Returns empty array for non-international or if disabled
     */
    private static function getDocumentImagesForShip(Ship $ship): array
    {
        // Only attach commercial invoice for international shipments (DHL requires it)
        $recipientCountry = $ship->customerAddress?->country_code ?? 'US';
        if ($recipientCountry === 'US') {
            return []; // Domestic shipments don't need customs documents
        }

        try {
            $invoiceService = app(CommercialInvoiceService::class);
            $invoiceBase64 = $invoiceService->getInvoiceBase64($ship);
            
            if ($invoiceBase64) {
                // Create DocumentImage DTO for DHL
                $documentImage = new DocumentImage(
                    content: $invoiceBase64,
                    imageFormat: 'PDF',
                    typeCode: 'INV' // Commercial Invoice
                );
                return [$documentImage];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::channel('carrier')->warning('Failed to generate commercial invoice for DHL', [
                'ship_id' => $ship->id,
                'error' => $e->getMessage(),
            ]);
            return []; // Fall back to DHL-generated invoice
        }
    }

    /**
     * Create ShipmentRequest from Ship using CarrierService model directly
     * Preferred method for new code using carrier_services table
     */
    public static function fromShipWithCarrierService(Ship $ship, array $packages, CarrierService $carrierService): self
    {
        $ship->load('customerAddress', 'user', 'customer');

        // Get warehouse origin address from first package
        $firstPackage = $packages[0] ?? null;
        $warehouse = $firstPackage?->warehouse;

        // Get recipient address
        $recipientAddr = $ship->customerAddress;

        // Build package details
        $packageDetails = [];
        $commodities = [];
        $dangerousGoodsVas = []; // Collect DG items for VAS

        foreach ($packages as $pkg) {
            $pkg->load('items');

            // Aggregate dimensions from items (Package model doesn't have direct l/w/h fields)
            $maxLength = $pkg->items->max('length') ?? 0;
            $maxWidth = $pkg->items->max('width') ?? 0;
            $maxHeight = $pkg->items->max('height') ?? 0;

            // Use billable weight (max of physical or volumetric) - this is what carriers charge on
            $packageWeight = (float) ($pkg->billed_weight ?? $pkg->total_weight ?? $pkg->weight ?? 0);

            $packageDetails[] = new PackageDetail(
                weight: $packageWeight > 0 ? $packageWeight : 1.0,
                weightUnit: $pkg->weight_unit ?? 'LB',
                length: (float) $maxLength,
                width: (float) $maxWidth,
                height: (float) $maxHeight,
                dimensionUnit: strtoupper($pkg->dimension_unit ?? 'IN'),
                declaredValue: (float) $pkg->total_value,
            );

            // Build commodities for customs
            foreach ($pkg->items as $item) {
                $commodities[] = new CommodityDetail(
                    description: $item->title,
                    quantity: (int) $item->quantity,
                    unitValue: (float) $item->value_per_unit,
                    totalValue: (float) $item->total_line_value,
                    weight: (float) ($item->weight_per_unit ?? 0) * $item->quantity,
                    weightUnit: $item->weight_unit ?? 'LB',
                    hsCode: $item->hs_code,
                    countryOfOrigin: $item->country_of_origin ?? 'US',
                    material: $item->material,
                );

                // Collect dangerous goods items for VAS
                if ($item->is_dangerous && $item->un_code) {
                    $dgClass = $item->dangerous_goods_class ?? '9';
                    $serviceCode = in_array($dgClass, ['1', '7']) ? 'HH' : 'HE';
                    $contentId = self::mapDgClassToContentId($dgClass);

                    $dangerousGoodsVas[] = [
                        'serviceCode' => $serviceCode,
                        'dangerousGoods' => [
                            [
                                'contentId' => $contentId,
                                'unCode' => $item->un_code,
                            ],
                        ],
                    ];
                }
            }
        }

        // Build valueAddedServices from collected DG items
        $valueAddedServices = !empty($dangerousGoodsVas) ? $dangerousGoodsVas : [];

        // Get service code from CarrierService
        $serviceType = $carrierService->getApiServiceCode();
        $declaredValue = $ship->declared_value ?? (float) $ship->total_price;

        // Get recipient tax ID from ship (national_id) or customer's saved tax_id
        $recipientTaxId = $ship->national_id ?? $ship->customer?->tax_id ?? null;

        return new self(
            senderName: $warehouse?->full_name ?? config('carriers.default_sender_name', 'Marketz Warehouse'),
            senderCompany: $warehouse?->company_name ?? config('carriers.default_sender_company', 'Marketz LLC'),
            senderPhone: $warehouse?->phone_number ?? config('carriers.default_sender_phone'),
            senderEmail: config('carriers.default_sender_email', 'shipping@marketz.com'),
            senderAddress: new Address(
                street1: $warehouse?->address ?? config('carriers.default_sender_address'),
                street2: $warehouse?->address_line_2,
                city: $warehouse?->city ?? config('carriers.default_sender_city'),
                state: $warehouse?->state ?? config('carriers.default_sender_state'),
                postalCode: $warehouse?->zip ?? config('carriers.default_sender_zip'),
                countryCode: $warehouse?->country_code ?? 'US',
            ),
            recipientName: $recipientAddr->full_name ?? '',
            recipientPhone: $recipientAddr->phone_number ?? '',
            recipientEmail: $ship->user?->email ?? '',
            recipientAddress: new Address(
                street1: $recipientAddr->address_line_1 ?? '',
                street2: $recipientAddr->address_line_2,
                city: $recipientAddr->city ?? '',
                state: $recipientAddr->state ?? '',
                postalCode: $recipientAddr->postal_code ?? '',
                countryCode: $recipientAddr->country_code ?? 'US',
            ),
            packages: $packageDetails,
            serviceType: $serviceType,
            packagingType: self::getCompatiblePackagingType($serviceType, $firstPackage?->package_type),
            shipDate: now()->format('Y-m-d'),
            declaredValue: $declaredValue,
            currency: $ship->declared_value_currency ?? 'USD',
            commodities: $commodities,
            referenceNumber: $ship->tracking_number,
            recipientTaxId: $recipientTaxId,
            // DHL Export Compliance - use Ship fields with sensible defaults
            usFilingTypeValue: $ship->us_filing_type ?? ($declaredValue < 2500 ? '30.37(a)' : null),
            incoterm: $ship->incoterm ?? 'DAP',
            invoiceSignatureName: $ship->invoice_signature_name ?? $warehouse?->full_name ?? config('carriers.default_sender_name', 'Authorized Shipper'),
            invoiceSignatureTitle: $ship->invoice_signature_title ?? 'Mr.',
            exporterId: $ship->exporter_id ?? 'EAR99',
            exporterCode: $ship->exporter_code ?? 'EXPCZ',
            valueAddedServices: $valueAddedServices, // DHL VAS for dangerous goods
        );
    }

    /**
     * Map InternationalShippingOptions ID to carrier service type
     * Automatically detects domestic vs international based on destination
     * 
     * @deprecated Use CarrierService model and getApiServiceCode() for new code
     */
    private static function mapShippingOptionToService(int|string|null $optionId, string $destinationCountry = 'US'): ?string
    {
        if (!$optionId) {
            return null;
        }

        // First try to find in new carrier_services table if it's a numeric ID
        if (is_numeric($optionId)) {
            $carrierService = CarrierService::find((int) $optionId);
            if ($carrierService) {
                return $carrierService->getApiServiceCode();
            }
        }

        // If it's already a valid carrier service code from live API, use it directly
        // Service codes are typically uppercase with underscores like FEDEX_GROUND
        $upperCode = strtoupper($optionId);
        $validServiceCodes = [
            // FedEx domestic
            'FEDEX_GROUND',
            'FEDEX_HOME_DELIVERY',
            'GROUND_HOME_DELIVERY',
            'FEDEX_EXPRESS_SAVER',
            'FEDEX_2_DAY',
            'FEDEX_2_DAY_AM',
            'STANDARD_OVERNIGHT',
            'PRIORITY_OVERNIGHT',
            'FIRST_OVERNIGHT',
            // FedEx international
            'FEDEX_INTERNATIONAL_PRIORITY',
            'FEDEX_INTERNATIONAL_ECONOMY',
            'INTERNATIONAL_FIRST',
            'FEDEX_INTERNATIONAL_GROUND',
            'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
            // DHL
            'EXPRESS_WORLDWIDE',
            'EXPRESS_9_00',
            'EXPRESS_10_30',
            'EXPRESS_12_00',
            'ECONOMY_SELECT',
            // UPS
            'UPS_GROUND',
            'UPS_NEXT_DAY_AIR',
            'UPS_2ND_DAY_AIR',
            'UPS_WORLDWIDE_EXPRESS',
            'UPS_WORLDWIDE_EXPEDITED',
            'UPS_STANDARD',
        ];

        if (in_array($upperCode, $validServiceCodes)) {
            return $upperCode;
        }

        // If it's already a valid carrier service code from live API, use it directly
        // Service codes are typically uppercase with underscores like FEDEX_GROUND
        $upperCode = strtoupper($optionId);
        $validServiceCodes = [
            // FedEx domestic
            'FEDEX_GROUND',
            'FEDEX_HOME_DELIVERY',
            'GROUND_HOME_DELIVERY',
            'FEDEX_EXPRESS_SAVER',
            'FEDEX_2_DAY',
            'FEDEX_2_DAY_AM',
            'STANDARD_OVERNIGHT',
            'PRIORITY_OVERNIGHT',
            'FIRST_OVERNIGHT',
            // FedEx international
            'FEDEX_INTERNATIONAL_PRIORITY',
            'FEDEX_INTERNATIONAL_ECONOMY',
            'INTERNATIONAL_FIRST',
            'FEDEX_INTERNATIONAL_GROUND',
            'FEDEX_INTERNATIONAL_PRIORITY_EXPRESS',
            // DHL
            'EXPRESS_WORLDWIDE',
            'EXPRESS_9_00',
            'EXPRESS_10_30',
            'EXPRESS_12_00',
            'ECONOMY_SELECT',
            // UPS
            'UPS_GROUND',
            'UPS_NEXT_DAY_AIR',
            'UPS_2ND_DAY_AIR',
            'UPS_WORLDWIDE_EXPRESS',
            'UPS_WORLDWIDE_EXPEDITED',
            'UPS_STANDARD',
        ];

        if (in_array($upperCode, $validServiceCodes)) {
            return $upperCode;
        }

        // Check if it looks like a service code pattern (CARRIER_SERVICE format)
        if (preg_match('/^[A-Z0-9_]+$/', $upperCode) && strlen($upperCode) > 5) {
            return $upperCode; // Pass through as-is
        }

        // If numeric string, convert to int for legacy handling
        if (is_numeric($optionId)) {
            $optionId = (int) $optionId;
        }

        // If still a string but not recognized as service code, try to detect carrier
        if (is_string($optionId)) {
            $lowerCode = strtolower($optionId);
            $senderCountry = config('carriers.default_sender_country', 'US');
            $isDomestic = strtoupper($destinationCountry) === strtoupper($senderCountry);

            if (str_contains($lowerCode, 'fedex')) {
                return $isDomestic ? 'FEDEX_GROUND' : 'FEDEX_INTERNATIONAL_PRIORITY';
            }
            if (str_contains($lowerCode, 'dhl')) {
                return 'EXPRESS_WORLDWIDE';
            }
            if (str_contains($lowerCode, 'ups')) {
                return $isDomestic ? 'UPS_GROUND' : 'UPS_WORLDWIDE_EXPRESS';
            }
        }

        // Determine if this is a domestic or international shipment for legacy DB IDs
        // Warehouse is in US, so if destination is US, it's domestic
        $senderCountry = config('carriers.default_sender_country', 'US');
        $isDomestic = strtoupper($destinationCountry) === strtoupper($senderCountry);

        // For domestic shipments, use domestic service types (legacy DB ID mapping)
        if ($isDomestic) {
            return match (true) {
                is_numeric($optionId) && in_array((int) $optionId, [1, 2]) => 'FEDEX_GROUND',
                is_numeric($optionId) && in_array((int) $optionId, [3, 4]) => 'EXPRESS_WORLDWIDE', // DHL
                is_numeric($optionId) && in_array((int) $optionId, [5, 6]) => 'UPS_GROUND',
                default => 'FEDEX_GROUND', // Default domestic service
            };
        }

        // For international shipments, use international service types (legacy DB ID mapping)
        return match (true) {
            is_numeric($optionId) && in_array((int) $optionId, [1, 2]) => 'FEDEX_INTERNATIONAL_PRIORITY',
            is_numeric($optionId) && in_array((int) $optionId, [3, 4]) => 'EXPRESS_WORLDWIDE',
            is_numeric($optionId) && in_array((int) $optionId, [5, 6]) => 'UPS_WORLDWIDE_EXPRESS',
            default => 'FEDEX_INTERNATIONAL_PRIORITY',
        };
    }

    /**
     * Get compatible packaging type based on service
     * Ground services require YOUR_PACKAGING, Express can use carrier boxes
     */
    private static function getCompatiblePackagingType(string $serviceType, ?string $packageType): string
    {
        // Ground services only accept YOUR_PACKAGING
        $groundServices = ['FEDEX_GROUND', 'GROUND', 'GROUND_HOME_DELIVERY', 'DOMESTIC_EXPRESS'];

        if (in_array($serviceType, $groundServices)) {
            return 'YOUR_PACKAGING';
        }

        // Valid FedEx packaging types for express/international services
        $validFedExPackagingTypes = [
            'YOUR_PACKAGING',
            'FEDEX_BOX',
            'FEDEX_ENVELOPE',
            'FEDEX_PAK',
            'FEDEX_TUBE',
            'FEDEX_10KG_BOX',
            'FEDEX_25KG_BOX',
            'FEDEX_SMALL_BOX',
            'FEDEX_MEDIUM_BOX',
            'FEDEX_LARGE_BOX',
            'FEDEX_EXTRA_LARGE_BOX',
        ];

        // If already a valid FedEx code, use it
        if ($packageType && in_array(strtoupper($packageType), $validFedExPackagingTypes)) {
            return strtoupper($packageType);
        }

        // Map common custom types to FedEx types
        $packageTypeMap = [
            'box' => 'YOUR_PACKAGING',
            'envelope' => 'FEDEX_ENVELOPE',
            'pak' => 'FEDEX_PAK',
            'tube' => 'FEDEX_TUBE',
            'small_box' => 'FEDEX_SMALL_BOX',
            'medium_box' => 'FEDEX_MEDIUM_BOX',
            'large_box' => 'FEDEX_LARGE_BOX',
        ];

        $normalizedType = strtolower($packageType ?? '');

        return $packageTypeMap[$normalizedType] ?? 'YOUR_PACKAGING';
    }

    /**
     * Map dangerous goods class to DHL contentId
     * Based on DHL MyDHL API dangerous goods content codes
     */
    private static function mapDgClassToContentId(string $dgClass): string
    {
        // DHL contentId codes for dangerous goods
        // See: DHL MyDHL API documentation - Dangerous Goods
        return match ($dgClass) {
            '1' => '100', // Explosives
            '2' => '200', // Gases
            '3' => '300', // Flammable Liquids
            '4' => '400', // Flammable Solids
            '5' => '500', // Oxidizers
            '6' => '600', // Toxic Substances
            '7' => '700', // Radioactive
            '8' => '800', // Corrosives
            '9' => '910', // Miscellaneous / Lithium Batteries
            default => '910', // Default to Class 9 (common for electronics)
        };
    }

    /**
     * Map customer-selected addon IDs to DHL valueAddedServices format
     * Converts CarrierAddon records to DHL VAS payload structure
     */
    private static function mapSelectedAddonsToDhlVas(array $addonIds, float $declaredValue, string $currency): array
    {
        if (empty($addonIds)) {
            return [];
        }

        // Fetch selected addons from database
        $addons = CarrierAddon::whereIn('id', $addonIds)->get();

        // Map addon_code to DHL VAS code
        $addonToDhlCode = [
            'insurance' => 'II',
            'extra_handling' => 'HK',
            'signature_required' => 'SB',
            'dangerous_goods' => 'HE',
            'dangerous_goods_full' => 'HH',
            'dry_ice' => 'DD',
            'saturday_delivery' => 'SA',
            'priority_handling' => 'PT',
            'hold_at_location' => 'WY',
        ];

        $vasItems = [];
        foreach ($addons as $addon) {
            $dhlCode = $addonToDhlCode[$addon->addon_code] ?? null;

            if (!$dhlCode) {
                // Skip addons without DHL mapping
                continue;
            }

            $vasItem = ['serviceCode' => $dhlCode];

            // Insurance requires value and currency
            if ($dhlCode === 'II' && $declaredValue > 0) {
                $vasItem['value'] = $declaredValue;
                $vasItem['currency'] = $currency;
            }

            $vasItems[] = $vasItem;
        }

        return $vasItems;
    }
}
