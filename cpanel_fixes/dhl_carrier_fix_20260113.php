#!/usr/bin/env php
<?php
/**
 * DHL Carrier Fix - January 13, 2026 (v2)
 * 
 * This script applies the following fixes to DHLCarrier.php:
 * 1. Postal code handling - Use 'N/A' for countries without postal codes
 * 2. Address fallback - Use 'N/A' instead of unsetting postalCode in retry
 * 3. Shipment creation - Add required 'companyName' to receiver contact
 * 4. Export declaration - Make commodityCode conditional (only valid HS codes)
 * 5. addressLine2 - Only include when non-empty string (fixes type error)
 * 
 * Usage: /usr/local/bin/ea-php81 cpanel_fixes/dhl_carrier_fix_20260113.php
 */

// Find the base path
$basePath = dirname(__DIR__);
$dhlCarrierPath = $basePath . '/app/Carriers/DHL/DHLCarrier.php';

if (!file_exists($dhlCarrierPath)) {
    die("Error: DHLCarrier.php not found at: $dhlCarrierPath\n");
}

echo "Applying DHL Carrier fixes (v2)...\n";

// Read current file
$content = file_get_contents($dhlCarrierPath);
$originalContent = $content;

// Fix 1: Update getRatesWithStrippedAddress to use 'N/A' instead of unsetting
$oldPattern1 = "// Strip state and postal code from receiver address
        if (isset(\$payload['customerDetails']['receiverDetails'])) {
            unset(\$payload['customerDetails']['receiverDetails']['provinceCode']);
            unset(\$payload['customerDetails']['receiverDetails']['postalCode']);
        }";

$newPattern1 = "// Strip state from receiver address, but keep postalCode as 'N/A' (DHL requires it)
        if (isset(\$payload['customerDetails']['receiverDetails'])) {
            unset(\$payload['customerDetails']['receiverDetails']['provinceCode']);
            // DHL requires postalCode - use 'N/A' for countries without postal codes
            \$payload['customerDetails']['receiverDetails']['postalCode'] = 'N/A';
        }";

if (strpos($content, $oldPattern1) !== false) {
    $content = str_replace($oldPattern1, $newPattern1, $content);
    echo "✓ Fix 1: Updated getRatesWithStrippedAddress to use 'N/A' postalCode\n";
} else {
    echo "○ Fix 1: Already applied or pattern not found\n";
}

// Fix 2: Update formatAddress to use 'N/A' for postal code
$oldPattern2 = "// DHL requires postal code - use placeholder for countries without them
            \$addressData['postalCode'] = \$address->postalCode ?: '00000';";

$newPattern2 = "// DHL requires postal code - use 'N/A' for countries without them
            \$addressData['postalCode'] = \$address->postalCode ?: 'N/A';";

if (strpos($content, $oldPattern2) !== false) {
    $content = str_replace($oldPattern2, $newPattern2, $content);
    echo "✓ Fix 2: Updated formatAddress to use 'N/A' postal code\n";
} else {
    echo "○ Fix 2: Already applied or pattern not found\n";
}

// Fix 3: Add companyName to receiverDetails contactInformation
$oldPattern3 = "'receiverDetails' => [
                    'postalAddress' => \$this->formatAddress(\$request->recipientAddress),
                    'contactInformation' => [
                        'phone' => \$request->recipientPhone,
                        'fullName' => \$request->recipientName,
                    ],
                ],";

$newPattern3 = "'receiverDetails' => [
                    'postalAddress' => \$this->formatAddress(\$request->recipientAddress),
                    'contactInformation' => [
                        'phone' => \$request->recipientPhone,
                        'companyName' => \$request->recipientName, // DHL requires companyName - use recipient name
                        'fullName' => \$request->recipientName,
                    ],
                ],";

if (strpos($content, $oldPattern3) !== false) {
    $content = str_replace($oldPattern3, $newPattern3, $content);
    echo "✓ Fix 3: Added companyName to receiverDetails contactInformation\n";
} else {
    echo "○ Fix 3: Already applied or pattern not found\n";
}

// Fix 4: Update formatCommodities to make commodityCode conditional
$oldPattern4 = "private function formatCommodities(array \$commodities): array
    {
        return array_map(fn(\$item, \$i) => [
            'number' => \$i + 1,
            'description' => \$item->description,
            'price' => \$item->totalValue,
            'priceCurrency' => 'USD',
            'quantity' => [
                'value' => \$item->quantity,
                'unitOfMeasurement' => 'PCS',
            ],
            'manufacturerCountry' => \$item->countryOfOrigin ?? 'US',
            'commodityCode' => \$item->hsCode,
            'weight' => [
                'grossValue' => \$item->weightUnit === 'LB' ? \$item->weight * 0.453592 : \$item->weight,
            ],
        ], \$commodities, array_keys(\$commodities));
    }";

$newPattern4 = "private function formatCommodities(array \$commodities): array
    {
        return array_map(function(\$item, \$i) {
            \$lineItem = [
                'number' => \$i + 1,
                'description' => \$item->description,
                'price' => \$item->totalValue,
                'priceCurrency' => 'USD',
                'quantity' => [
                    'value' => \$item->quantity,
                    'unitOfMeasurement' => 'PCS',
                ],
                'manufacturerCountry' => \$item->countryOfOrigin ?? 'US',
                'weight' => [
                    'grossValue' => \$item->weightUnit === 'LB' ? \$item->weight * 0.453592 : \$item->weight,
                ],
            ];
            
            // Only add commodityCode (HS code) if valid - DHL rejects invalid/empty codes
            if (!empty(\$item->hsCode) && preg_match('/^\d{6,10}\$/', \$item->hsCode)) {
                \$lineItem['commodityCode'] = \$item->hsCode;
            }
            
            return \$lineItem;
        }, \$commodities, array_keys(\$commodities));
    }";

if (strpos($content, $oldPattern4) !== false) {
    $content = str_replace($oldPattern4, $newPattern4, $content);
    echo "✓ Fix 4: Updated formatCommodities to make commodityCode conditional\n";
} else {
    echo "○ Fix 4: Already applied or pattern not found\n";
}

// Fix 5: Update street2/addressLine2 handling to prevent null type errors
$oldPattern5 = "// Add street2 if available
        if (!empty(\$address->street2)) {
            \$addressData['addressLine2'] = \$address->street2;
        }";

$newPattern5 = "// Add street2 only if it's a non-empty string (DHL rejects null/non-string values)
        \$street2 = \$address->street2 ?? '';
        if (is_string(\$street2) && trim(\$street2) !== '') {
            \$addressData['addressLine2'] = trim(\$street2);
        }";

if (strpos($content, $oldPattern5) !== false) {
    $content = str_replace($oldPattern5, $newPattern5, $content);
    echo "✓ Fix 5: Updated street2 handling to prevent null type errors\n";
} else {
    echo "○ Fix 5: Already applied or pattern not found\n";
}

// Write file if changes were made
if ($content !== $originalContent) {
    // Backup original
    $backupPath = $dhlCarrierPath . '.backup_' . date('Ymd_His');
    copy($dhlCarrierPath, $backupPath);
    echo "\n→ Backup created: $backupPath\n";

    // Write changes
    file_put_contents($dhlCarrierPath, $content);
    echo "→ Changes applied successfully!\n";
} else {
    echo "\n→ No changes needed - all fixes already applied.\n";
}

echo "\nDone.\n";
