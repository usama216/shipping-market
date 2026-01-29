<?php

/**
 * Test FedEx Shipment Payload
 * This script tests the FedEx shipment payload to identify validation issues
 */

require __DIR__ . '/vendor/autoload.php';

// Load Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Original payload from logs (with issues)
$originalPayload = [
    "labelResponseOptions" => "LABEL",
    "requestedShipment" => [
        "shipper" => [
            "contact" => [
                "personName" => "Shamiro",
                "phoneNumber" => "+19414910433"
            ],
            "address" => [
                "streetLines" => ["2900 NW 112th Ave", "Unit F2"],
                "city" => "Doral",
                "countryCode" => "US",
                "stateOrProvinceCode" => "FL",
                "postalCode" => "33172"
            ]
        ],
        "recipients" => [[
            "contact" => [
                "personName" => "Diego Salamanca",
                "phoneNumber" => "+1 721 587 6194"
            ],
            "address" => [
                "streetLines" => ["Wellfare Road 46A", "Colebay"],
                "city" => "Cole Bay",
                "countryCode" => "SX",
                "stateOrProvinceCode" => "SM", // Fixed: Added for Sint Maarten
                "postalCode" => "00000" // Fixed: Changed from empty string to "00000"
            ]
        ]],
        "shipDateStamp" => "2026-01-23", // Fixed: capital S in DateStamp
        "serviceType" => "FEDEX_INTERNATIONAL_PRIORITY",
        "packagingType" => "YOUR_PACKAGING",
        "pickupType" => "DROPOFF_AT_FEDEX_LOCATION", // Fixed: Changed from USE_SCHEDULED_PICKUP
        "blockInsightVisibility" => false,
        "shippingChargesPayment" => [
            "paymentType" => "SENDER",
            "payor" => [
                "responsibleParty" => [
                    "accountNumber" => ["value" => "209305355"] // Fixed: string not float
                ]
            ]
        ],
        "labelSpecification" => [
            "imageType" => "PDF",
            "labelStockType" => "STOCK_4X6" // Fixed: Changed from PAPER_4X6 (invalid)
        ],
        "requestedPackageLineItems" => [[
            "weight" => [
                "value" => 19.8, // Fixed: rounded to 1 decimal
                "units" => "LB"
            ],
            "dimensions" => [
                "length" => 22,
                "width" => 12,
                "height" => 6,
                "units" => "IN"
            ],
            "declaredValue" => [
                "amount" => 430.56, // Fixed: rounded to 2 decimals
                "currency" => "USD"
            ],
            "customerReferences" => [[
                "customerReferenceType" => "CUSTOMER_REFERENCE",
                "value" => "91436106" // Fixed: string not float
            ]]
        ]],
        "customsClearanceDetail" => [
            "dutiesPayment" => [
                "paymentType" => "SENDER"
            ],
            "isDocumentOnly" => false,
            "commercialInvoice" => [
                "purpose" => "SOLD",
                "termsOfSale" => "DAP" // Fixed: Changed from DDU (FedEx no longer accepts DDU)
            ],
            "commodities" => [[
                "description" => "Fire Wallbox",
                "quantity" => 9,
                "quantityUnits" => "EA", // Fixed: Changed from PCS (invalid) to EA
                "weight" => [
                    "value" => 19.8, // Fixed: rounded to 1 decimal
                    "units" => "LB"
                ],
                "unitPrice" => [
                    "amount" => 47.84, // Fixed: rounded to 2 decimals
                    "currency" => "USD"
                ],
                "customsValue" => [
                    "amount" => 430.56, // Fixed: rounded to 2 decimals
                    "currency" => "USD"
                ],
                "countryOfManufacture" => "US",
                "harmonizedCode" => "853110" // Fixed: Padded to 6 digits (8531 -> 853110)
            ]],
            // "exportDetail" => [
            //     "exportComplianceStatement" => "30.37(a)" // Commented out - may cause enum errors
            // ],
            "recipientCustomsId" => [
                "type" => "NATIONAL_ID", // Fixed: Changed from PERSONAL_COUNTRY (more reliable)
                "value" => "111111111" // Fixed: string not float
            ]
        ]
    ],
    "accountNumber" => ["value" => "209305355"] // Fixed: string not float
];

// Clean function to ensure proper numeric formatting
function cleanPayload($data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data[$key] = cleanPayload($value);
        } elseif (is_float($value)) {
            // Round floats based on context
            if ($key === 'value' && isset($data['units']) && in_array($data['units'], ['LB', 'KG'])) {
                $data[$key] = round($value, 1);
            } elseif ($key === 'amount') {
                $data[$key] = round($value, 2);
            } else {
                $data[$key] = $value;
            }
        }
    }
    return $data;
}

$cleanedPayload = cleanPayload($originalPayload);

// Output the cleaned payload
echo "=== CLEANED PAYLOAD ===\n";
echo json_encode($cleanedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Check for potential enum issues
echo "=== CHECKING ENUM VALUES ===\n";
echo "serviceType: " . $cleanedPayload['requestedShipment']['serviceType'] . "\n";
echo "packagingType: " . $cleanedPayload['requestedShipment']['packagingType'] . "\n";
echo "pickupType: " . $cleanedPayload['requestedShipment']['pickupType'] . "\n";
echo "paymentType: " . $cleanedPayload['requestedShipment']['shippingChargesPayment']['paymentType'] . "\n";
echo "imageType: " . $cleanedPayload['requestedShipment']['labelSpecification']['imageType'] . "\n";
echo "labelStockType: " . $cleanedPayload['requestedShipment']['labelSpecification']['labelStockType'] . "\n";
echo "purpose: " . $cleanedPayload['requestedShipment']['customsClearanceDetail']['commercialInvoice']['purpose'] . "\n";
echo "termsOfSale: " . $cleanedPayload['requestedShipment']['customsClearanceDetail']['commercialInvoice']['termsOfSale'] . "\n";
echo "quantityUnits: " . $cleanedPayload['requestedShipment']['customsClearanceDetail']['commodities'][0]['quantityUnits'] . "\n";
echo "recipientCustomsId.type: " . $cleanedPayload['requestedShipment']['customsClearanceDetail']['recipientCustomsId']['type'] . "\n";
if (isset($cleanedPayload['requestedShipment']['customsClearanceDetail']['exportDetail']['exportComplianceStatement'])) {
    echo "exportComplianceStatement: " . $cleanedPayload['requestedShipment']['customsClearanceDetail']['exportDetail']['exportComplianceStatement'] . "\n";
} else {
    echo "exportComplianceStatement: (not included)\n";
}

echo "\n=== CHECKING FOR COMMON ISSUES ===\n";
// Check for float values that should be strings
function findFloatIssues($data, $path = '') {
    $issues = [];
    foreach ($data as $key => $value) {
        $currentPath = $path ? "$path.$key" : $key;
        if (is_array($value)) {
            $issues = array_merge($issues, findFloatIssues($value, $currentPath));
        } elseif (is_float($value)) {
            // Check if this should be a string (account numbers, references, etc.)
            if (str_contains($currentPath, 'accountNumber') || 
                str_contains($currentPath, 'customerReferences') ||
                str_contains($currentPath, 'recipientCustomsId')) {
                $issues[] = "$currentPath: float value ($value) should be string";
            } elseif (str_contains($currentPath, 'weight') && str_contains($currentPath, 'value')) {
                $rounded = round($value, 1);
                if ($value != $rounded) {
                    $issues[] = "$currentPath: excessive decimals ($value) should be $rounded";
                }
            } elseif (str_contains($currentPath, 'amount')) {
                $rounded = round($value, 2);
                if ($value != $rounded) {
                    $issues[] = "$currentPath: excessive decimals ($value) should be $rounded";
                }
            }
        }
    }
    return $issues;
}

$issues = findFloatIssues($cleanedPayload);
if (empty($issues)) {
    echo "✓ No numeric formatting issues found\n";
} else {
    echo "Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
}

echo "\n=== PAYLOAD READY FOR TESTING ===\n";
