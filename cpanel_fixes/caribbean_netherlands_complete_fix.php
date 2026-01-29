<?php
/**
 * Caribbean Netherlands Island Separation - COMPLETE cPanel Fix
 * 
 * This script applies ALL changes needed for the island separation:
 * 1. Database schema changes (carrier_code column, expand code column)
 * 2. Insert new island countries (BQ-BO, BQ-SA, BQ-SE)
 * 3. Patch Country.php with getCarrierCode() method
 * 4. Patch Customer.php with updated suite prefixes  
 * 5. Patch DHLCarrier.php with carrier_code mapping
 * 6. Patch FedExCarrier.php with carrier_code mapping
 * 
 * Run: php cpanel_fixes/caribbean_netherlands_complete_fix.php
 * Or via browser: https://marketsz.com/cpanel_fixes/caribbean_netherlands_complete_fix.php?run=1
 * 
 * Date: 2026-01-13
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('Add ?run=1 to execute this script');
}

echo "=== Caribbean Netherlands COMPLETE Fix ===\n\n";

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$basePath = dirname(__DIR__);

// ============================================================================
// PART 1: DATABASE CHANGES
// ============================================================================
echo "PART 1: Database Changes\n";
echo str_repeat("-", 50) . "\n";

try {
    DB::beginTransaction();

    // Add carrier_code column
    if (!Schema::hasColumn('countries', 'carrier_code')) {
        DB::statement("ALTER TABLE countries ADD COLUMN carrier_code VARCHAR(2) NULL AFTER code");
        echo "  ✓ carrier_code column added\n";
    } else {
        echo "  - carrier_code column already exists\n";
    }

    // Expand code column
    DB::statement("ALTER TABLE countries MODIFY COLUMN code VARCHAR(10) NOT NULL");
    echo "  ✓ code column expanded to VARCHAR(10)\n";

    // Insert islands if they don't exist
    $islands = [
        ['name' => 'Bonaire', 'code' => 'BQ-BO', 'carrier_code' => 'BQ', 'phone_prefix' => '+599-7', 'sort_order' => 6],
        ['name' => 'Saba', 'code' => 'BQ-SA', 'carrier_code' => 'BQ', 'phone_prefix' => '+599-4', 'sort_order' => 7],
        ['name' => 'Sint Eustatius', 'code' => 'BQ-SE', 'carrier_code' => 'BQ', 'phone_prefix' => '+599-3', 'sort_order' => 8],
    ];

    foreach ($islands as $island) {
        $exists = DB::table('countries')->where('code', $island['code'])->exists();
        if (!$exists) {
            DB::table('countries')->insert([
                'name' => $island['name'],
                'code' => $island['code'],
                'carrier_code' => $island['carrier_code'],
                'phone_prefix' => $island['phone_prefix'],
                'has_postal_code' => false,
                'fedex_accepts_state' => false,
                'dhl_accepts_state' => false,
                'is_active' => true,
                'sort_order' => $island['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  ✓ {$island['name']} ({$island['code']}) inserted\n";
        } else {
            // Update carrier_code if missing
            DB::table('countries')->where('code', $island['code'])->update([
                'carrier_code' => $island['carrier_code'],
                'phone_prefix' => $island['phone_prefix'],
            ]);
            echo "  - {$island['name']} already exists, updated carrier_code\n";
        }
    }

    DB::commit();
    echo "  ✓ Database changes committed\n\n";

} catch (Exception $e) {
    DB::rollBack();
    echo "  ✗ Database error: " . $e->getMessage() . "\n\n";
}

// ============================================================================
// PART 2: PATCH Country.php
// ============================================================================
echo "PART 2: Patching Country.php\n";
echo str_repeat("-", 50) . "\n";

$countryFile = $basePath . '/app/Models/Country.php';
$countryContent = file_get_contents($countryFile);

// Check if getCarrierCode already exists
if (strpos($countryContent, 'getCarrierCode') === false) {
    // Add carrier_code to fillable if not present
    if (strpos($countryContent, "'carrier_code'") === false) {
        $countryContent = str_replace(
            "'code',\n        'phone_prefix',",
            "'code',\n        'carrier_code',     // ISO code for carrier APIs (e.g., BQ for Bonaire/Saba/Sint Eustatius)\n        'phone_prefix',",
            $countryContent
        );
        echo "  ✓ Added carrier_code to fillable\n";
    }

    // Add getCarrierCode method before the states() method
    $getCarrierCodeMethod = <<<'PHP'

    /**
     * Get the carrier-compatible country code (ISO 2-letter).
     * For countries with internal codes (e.g., BQ-BO for Bonaire),
     * this returns the actual ISO code (BQ) for carrier API calls.
     */
    public function getCarrierCode(): string
    {
        return $this->carrier_code ?? $this->code;
    }

    /**
PHP;

    $countryContent = str_replace(
        "\n    /**\n     * Get all states/parishes",
        $getCarrierCodeMethod . "\n     * Get all states/parishes",
        $countryContent
    );

    file_put_contents($countryFile, $countryContent);
    echo "  ✓ Added getCarrierCode() method\n";
} else {
    echo "  - getCarrierCode() already exists\n";
}

echo "\n";

// ============================================================================
// PART 3: PATCH Customer.php - Suite Prefixes
// ============================================================================
echo "PART 3: Patching Customer.php (Suite Prefixes)\n";
echo str_repeat("-", 50) . "\n";

$customerFile = $basePath . '/app/Models/Customer.php';
$customerContent = file_get_contents($customerFile);

// Check if new prefixes already exist
if (strpos($customerContent, "'BQ-BO' => 'XB'") === false) {
    // Find and replace the prefixMap
    $oldPrefixMap = <<<'PHP'
        // Island code to prefix mapping (X + island initial)
        $prefixMap = [
            'AI' => 'XA',  // Anguilla
            'AG' => 'XA',  // Antigua and Barbuda
            'AW' => 'XA',  // Aruba
            'BS' => 'XB',  // Bahamas
            'BB' => 'XB',  // Barbados
            'BQ' => 'XC',  // Caribbean Netherlands (Bonaire, Saba, Sint Eustatius)
            'VG' => 'XB',  // British Virgin Islands
            'KY' => 'XC',  // Cayman Islands
            'CW' => 'XC',  // Curaçao
            'DM' => 'XD',  // Dominica
            'DO' => 'XD',  // Dominican Republic
            'GD' => 'XG',  // Grenada
            'GP' => 'XG',  // Guadeloupe
            'HT' => 'XH',  // Haiti
            'JM' => 'XJ',  // Jamaica
            'MQ' => 'XM',  // Martinique
            'MS' => 'XM',  // Montserrat
            'PR' => 'XP',  // Puerto Rico
            'BL' => 'XS',  // Saint Barthélemy
            'KN' => 'XS',  // Saint Kitts and Nevis
            'LC' => 'XS',  // Saint Lucia
            'MF' => 'XS',  // Saint Martin (French)
            'VC' => 'XS',  // Saint Vincent and the Grenadines
            'SX' => 'XS',  // Sint Maarten (Dutch)
            'TT' => 'XT',  // Trinidad and Tobago
            'TC' => 'XT',  // Turks and Caicos
            'VI' => 'XV',  // U.S. Virgin Islands
        ];
PHP;

    $newPrefixMap = <<<'PHP'
        // Island/country code to suite prefix mapping (from business requirements)
        $prefixMap = [
            // Caribbean Netherlands BES Islands (split from BQ)
            'BQ-BO' => 'XB',     // Bonaire
            'BQ-SE' => 'XE',     // Sint Eustatius
            'BQ-SA' => 'AN',     // Saba
            
            // ABC Islands
            'CW' => 'XC',        // Curaçao
            'AW' => 'XA',        // Aruba
            
            // Dutch Caribbean
            'SX' => 'XM',        // Sint Maarten (Dutch & French)
            
            // Lesser Antilles
            'AI' => 'XAI',       // Anguilla
            'AG' => 'XAG',       // Antigua and Barbuda
            'BS' => 'XBA',       // Bahamas
            'BB' => 'XBB',       // Barbados
            'VG' => 'XBVI',      // British Virgin Islands
            'KY' => 'XCYM',      // Cayman Islands
            'CU' => 'XCUB',      // Cuba
            'DM' => 'XDM',       // Dominica
            'DO' => 'XD',        // Dominican Republic
            'GD' => 'XG',        // Grenada
            'GP' => 'XGUA',      // Guadeloupe
            'HT' => 'XH',        // Haiti
            'JM' => 'XJ',        // Jamaica
            'MQ' => 'XMAR',      // Martinique
            'MS' => 'XMONT',     // Montserrat
            'PR' => 'XP',        // Puerto Rico
            'BL' => 'XBLM',      // Saint Barthélemy
            'KN' => 'XK',        // Saint Kitts and Nevis
            'LC' => 'XL',        // Saint Lucia
            'MF' => 'XMAF',      // Saint Martin (French Collectivity)
            'VC' => 'XV',        // Saint Vincent and the Grenadines
            'TT' => 'XT',        // Trinidad and Tobago
            'TC' => 'XTCA',      // Turks and Caicos Islands
            'VI' => 'XUSVI',     // United States Virgin Islands
            
            // Legacy fallback for existing BQ customers
            'BQ' => 'XC',
        ];
PHP;

    if (strpos($customerContent, $oldPrefixMap) !== false) {
        $customerContent = str_replace($oldPrefixMap, $newPrefixMap, $customerContent);
        echo "  ✓ Replaced prefixMap with new suite prefixes\n";
    } else {
        echo "  ! Could not find old prefixMap pattern - manual update may be needed\n";
    }

    // Also fix the SUBSTRING offset for variable-length prefixes
    $customerContent = str_replace(
        "->orderByRaw('CAST(SUBSTRING(suite, 3) AS UNSIGNED) DESC')",
        "->orderByRaw('CAST(SUBSTRING(suite, ' . (strlen(\$prefix) + 1) . ') AS UNSIGNED) DESC')",
        $customerContent
    );

    $customerContent = str_replace(
        "preg_match('/^' . \$prefix . '(\\d+)\$/'",
        "preg_match('/^' . preg_quote(\$prefix, '/') . '(\\d+)\$/'",
        $customerContent
    );

    file_put_contents($customerFile, $customerContent);
    echo "  ✓ Fixed suite number extraction for variable-length prefixes\n";
} else {
    echo "  - New suite prefixes already exist\n";
}

echo "\n";

// ============================================================================
// PART 4: PATCH DHLCarrier.php
// ============================================================================
echo "PART 4: Patching DHLCarrier.php\n";
echo str_repeat("-", 50) . "\n";

$dhlFile = $basePath . '/app/Carriers/DHL/DHLCarrier.php';
if (file_exists($dhlFile)) {
    $dhlContent = file_get_contents($dhlFile);

    if (strpos($dhlContent, 'getCarrierCode()') === false) {
        // Update formatAddress to use carrier_code
        $oldDhlPattern = "\$countryCode = strtoupper(\$this->sanitizeString(\$address->countryCode ?? 'US', 2)) ?: 'US';

        // Query database for country-specific rules
        \$country = Country::where('code', \$countryCode)->first();
        \$hasPostalCode = \$country?->has_postal_code ?? true;";

        $newDhlPattern = "\$countryCode = strtoupper(\$this->sanitizeString(\$address->countryCode ?? 'US', 5)) ?: 'US';

        // Query database for country-specific rules
        \$country = Country::where('code', \$countryCode)->first();
        
        // Use carrier_code for API calls (e.g., BQ-BO → BQ)
        \$carrierCountryCode = \$country?->getCarrierCode() ?? \$this->sanitizeString(\$countryCode, 2);
        
        \$hasPostalCode = \$country?->has_postal_code ?? true;";

        $dhlContent = str_replace($oldDhlPattern, $newDhlPattern, $dhlContent);

        // Update countryCode in addressData
        $dhlContent = str_replace(
            "'countryCode' => \$countryCode,",
            "'countryCode' => \$carrierCountryCode,",
            $dhlContent
        );

        file_put_contents($dhlFile, $dhlContent);
        echo "  ✓ Updated formatAddress() to use carrier_code\n";
    } else {
        echo "  - Already patched\n";
    }
} else {
    echo "  ! DHLCarrier.php not found\n";
}

echo "\n";

// ============================================================================
// PART 5: PATCH FedExCarrier.php
// ============================================================================
echo "PART 5: Patching FedExCarrier.php\n";
echo str_repeat("-", 50) . "\n";

$fedexFile = $basePath . '/app/Carriers/FedEx/FedExCarrier.php';
if (file_exists($fedexFile)) {
    $fedexContent = file_get_contents($fedexFile);

    if (strpos($fedexContent, 'getCarrierCode()') === false) {
        // Update formatContact to use carrier_code
        $oldFedexPattern = "\$countryCode = strtoupper(\$address->countryCode ?? 'US');

        // Query database for country-specific rules
        \$country = Country::where('code', \$countryCode)->first();
        \$requiresPostalCode = \$country ? \$country->has_postal_code : true;";

        $newFedexPattern = "\$countryCode = strtoupper(\$address->countryCode ?? 'US');

        // Query database for country-specific rules
        \$country = Country::where('code', \$countryCode)->first();
        
        // Use carrier_code for API calls (e.g., BQ-BO → BQ)
        \$carrierCountryCode = \$country?->getCarrierCode() ?? substr(\$countryCode, 0, 2);
        
        \$requiresPostalCode = \$country ? \$country->has_postal_code : true;";

        $fedexContent = str_replace($oldFedexPattern, $newFedexPattern, $fedexContent);

        // Update countryCode in addressData
        $fedexContent = str_replace(
            "'countryCode' => \$countryCode,",
            "'countryCode' => \$carrierCountryCode,",
            $fedexContent
        );

        file_put_contents($fedexFile, $fedexContent);
        echo "  ✓ Updated formatContact() to use carrier_code\n";
    } else {
        echo "  - Already patched\n";
    }
} else {
    echo "  ! FedExCarrier.php not found\n";
}

echo "\n";

// ============================================================================
// PART 6: PATCH Validation Rules (RegisteredUserController, WarehouseController)
// ============================================================================
echo "PART 6: Patching Validation Rules\n";
echo str_repeat("-", 50) . "\n";

// Patch RegisteredUserController
$registerFile = $basePath . '/app/Http/Controllers/Auth/RegisteredUserController.php';
if (file_exists($registerFile)) {
    $registerContent = file_get_contents($registerFile);

    if (strpos($registerContent, "'country_code' => 'required|string|max:2'") !== false) {
        $registerContent = str_replace(
            "'country_code' => 'required|string|max:2'",
            "'country_code' => 'required|string|max:10'",
            $registerContent
        );
        file_put_contents($registerFile, $registerContent);
        echo "  ✓ RegisteredUserController: country_code max:2 → max:10\n";
    } else {
        echo "  - RegisteredUserController: already patched or different format\n";
    }
}

// Patch WarehouseController
$warehouseFile = $basePath . '/app/Http/Controllers/Admin/WarehouseController.php';
if (file_exists($warehouseFile)) {
    $warehouseContent = file_get_contents($warehouseFile);

    if (strpos($warehouseContent, "'country_code' => 'required|string|max:3'") !== false) {
        $warehouseContent = str_replace(
            "'country_code' => 'required|string|max:3'",
            "'country_code' => 'required|string|max:10'",
            $warehouseContent
        );
        file_put_contents($warehouseFile, $warehouseContent);
        echo "  ✓ WarehouseController: country_code max:3 → max:10\n";
    } else {
        echo "  - WarehouseController: already patched or different format\n";
    }
}

echo "\n";

// ============================================================================
// VERIFICATION
// ============================================================================
echo "=== VERIFICATION ===\n";
echo str_repeat("-", 50) . "\n";

// Check database
$islands = DB::table('countries')
    ->whereIn('code', ['BQ-BO', 'BQ-SA', 'BQ-SE'])
    ->get(['name', 'code', 'carrier_code', 'phone_prefix']);

echo "Database entries:\n";
foreach ($islands as $island) {
    echo "  ✓ {$island->name} ({$island->code}) → carrier: {$island->carrier_code}\n";
}

// Test suite generation
echo "\nSuite number generation:\n";
$testPrefixes = [
    'BQ-BO' => 'XB',
    'BQ-SA' => 'AN',
    'BQ-SE' => 'XE',
];
foreach ($testPrefixes as $code => $expectedPrefix) {
    $suite = \App\Models\Customer::generateSuiteNumber($code, 'Test');
    $actual = substr($suite, 0, strlen($expectedPrefix));
    $status = ($actual === $expectedPrefix) ? '✓' : '✗';
    echo "  {$status} {$code} → {$suite} (expected prefix: {$expectedPrefix})\n";
}

echo "\n=== COMPLETE ===\n";

