<?php
/**
 * Caribbean Netherlands Island Separation - cPanel Fix
 * 
 * This script separates Caribbean Netherlands (BQ) into 3 independent island entries:
 * - Bonaire (BQ-BO)
 * - Saba (BQ-SA)
 * - Sint Eustatius (BQ-SE)
 * 
 * Run this script via cPanel's PHP execution or include it in a deployment script.
 * 
 * Date: 2026-01-13
 */

// Ensure this is run from command line or cPanel
if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('Add ?run=1 to execute this script');
}

echo "=== Caribbean Netherlands Island Separation Fix ===\n\n";

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

try {
    DB::beginTransaction();

    // Step 1: Add carrier_code column if not exists
    echo "Step 1: Adding carrier_code column...\n";
    if (!Schema::hasColumn('countries', 'carrier_code')) {
        DB::statement("ALTER TABLE countries ADD COLUMN carrier_code VARCHAR(2) NULL AFTER code");
        echo "  ✓ carrier_code column added\n";
    } else {
        echo "  - carrier_code column already exists\n";
    }

    // Step 2: Expand code column to support longer codes like BQ-BO
    echo "Step 2: Expanding code column...\n";
    DB::statement("ALTER TABLE countries MODIFY COLUMN code VARCHAR(10) NOT NULL");
    echo "  ✓ code column expanded to VARCHAR(10)\n";

    // Step 3: Check if BQ exists and needs to be split
    $existingBQ = DB::table('countries')->where('code', 'BQ')->first();

    if ($existingBQ) {
        echo "Step 3: Splitting Caribbean Netherlands (BQ) into 3 islands...\n";

        // Get states under BQ
        $bonaireState = DB::table('states')->where('country_id', $existingBQ->id)->where('name', 'Bonaire')->first();
        $sabaState = DB::table('states')->where('country_id', $existingBQ->id)->where('name', 'Saba')->first();
        $sintEustatiusState = DB::table('states')->where('country_id', $existingBQ->id)->where('name', 'Sint Eustatius')->first();

        // Insert Bonaire
        $bonaireId = DB::table('countries')->insertGetId([
            'name' => 'Bonaire',
            'code' => 'BQ-BO',
            'carrier_code' => 'BQ',
            'phone_prefix' => '+599-7',
            'has_postal_code' => false,
            'fedex_accepts_state' => false,
            'dhl_accepts_state' => false,
            'is_active' => true,
            'sort_order' => $existingBQ->sort_order,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  ✓ Bonaire (BQ-BO) created\n";

        // Insert Saba
        $sabaId = DB::table('countries')->insertGetId([
            'name' => 'Saba',
            'code' => 'BQ-SA',
            'carrier_code' => 'BQ',
            'phone_prefix' => '+599-4',
            'has_postal_code' => false,
            'fedex_accepts_state' => false,
            'dhl_accepts_state' => false,
            'is_active' => true,
            'sort_order' => $existingBQ->sort_order + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  ✓ Saba (BQ-SA) created\n";

        // Insert Sint Eustatius
        $sintEustatiusId = DB::table('countries')->insertGetId([
            'name' => 'Sint Eustatius',
            'code' => 'BQ-SE',
            'carrier_code' => 'BQ',
            'phone_prefix' => '+599-3',
            'has_postal_code' => false,
            'fedex_accepts_state' => false,
            'dhl_accepts_state' => false,
            'is_active' => true,
            'sort_order' => $existingBQ->sort_order + 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  ✓ Sint Eustatius (BQ-SE) created\n";

        // Move states to new countries
        if ($bonaireState) {
            DB::table('states')->where('id', $bonaireState->id)->update(['country_id' => $bonaireId]);
            echo "  ✓ Bonaire state moved\n";
        }
        if ($sabaState) {
            DB::table('states')->where('id', $sabaState->id)->update(['country_id' => $sabaId]);
            echo "  ✓ Saba state moved\n";
        }
        if ($sintEustatiusState) {
            DB::table('states')->where('id', $sintEustatiusState->id)->update(['country_id' => $sintEustatiusId]);
            echo "  ✓ Sint Eustatius state moved\n";
        }

        // Delete the old BQ entry (now empty of states)
        $remainingStates = DB::table('states')->where('country_id', $existingBQ->id)->count();
        if ($remainingStates == 0) {
            DB::table('countries')->where('id', $existingBQ->id)->delete();
            echo "  ✓ Old Caribbean Netherlands (BQ) entry deleted\n";
        } else {
            echo "  ! Old BQ entry still has {$remainingStates} states, keeping it\n";
        }
    } else {
        echo "Step 3: Caribbean Netherlands (BQ) not found, checking if islands already exist...\n";

        // Check if islands already exist
        $bonaire = DB::table('countries')->where('code', 'BQ-BO')->first();
        $saba = DB::table('countries')->where('code', 'BQ-SA')->first();
        $sintEustatius = DB::table('countries')->where('code', 'BQ-SE')->first();

        if (!$bonaire) {
            DB::table('countries')->insert([
                'name' => 'Bonaire',
                'code' => 'BQ-BO',
                'carrier_code' => 'BQ',
                'phone_prefix' => '+599-7',
                'has_postal_code' => false,
                'fedex_accepts_state' => false,
                'dhl_accepts_state' => false,
                'is_active' => true,
                'sort_order' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  ✓ Bonaire (BQ-BO) inserted\n";
        } else {
            echo "  - Bonaire already exists\n";
        }

        if (!$saba) {
            DB::table('countries')->insert([
                'name' => 'Saba',
                'code' => 'BQ-SA',
                'carrier_code' => 'BQ',
                'phone_prefix' => '+599-4',
                'has_postal_code' => false,
                'fedex_accepts_state' => false,
                'dhl_accepts_state' => false,
                'is_active' => true,
                'sort_order' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  ✓ Saba (BQ-SA) inserted\n";
        } else {
            echo "  - Saba already exists\n";
        }

        if (!$sintEustatius) {
            DB::table('countries')->insert([
                'name' => 'Sint Eustatius',
                'code' => 'BQ-SE',
                'carrier_code' => 'BQ',
                'phone_prefix' => '+599-3',
                'has_postal_code' => false,
                'fedex_accepts_state' => false,
                'dhl_accepts_state' => false,
                'is_active' => true,
                'sort_order' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "  ✓ Sint Eustatius (BQ-SE) inserted\n";
        } else {
            echo "  - Sint Eustatius already exists\n";
        }
    }

    DB::commit();
    echo "\n=== SUCCESS: Caribbean Netherlands islands separated ===\n";

    // Show results
    echo "\nNew island entries:\n";
    $islands = DB::table('countries')
        ->whereIn('code', ['BQ-BO', 'BQ-SA', 'BQ-SE'])
        ->get(['name', 'code', 'carrier_code', 'phone_prefix']);

    foreach ($islands as $island) {
        echo "  - {$island->name} ({$island->code}) → carrier: {$island->carrier_code}, phone: {$island->phone_prefix}\n";
    }

} catch (Exception $e) {
    DB::rollBack();
    echo "\n=== ERROR ===\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
