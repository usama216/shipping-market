<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carrier_commission_settings', function (Blueprint $table) {
            // Drop the old single commission_percentage column
            $table->dropColumn('commission_percentage');
        });

        Schema::table('carrier_commission_settings', function (Blueprint $table) {
            // Add per-carrier commission columns
            $table->decimal('dhl_commission_percentage', 5, 2)->default(15.00)->after('id')->comment('DHL commission percentage');
            $table->decimal('fedex_commission_percentage', 5, 2)->default(15.00)->after('dhl_commission_percentage')->comment('FedEx commission percentage');
            $table->decimal('ups_commission_percentage', 5, 2)->default(15.00)->after('fedex_commission_percentage')->comment('UPS commission percentage');
        });

        // Migrate existing data: if there's an existing record, copy the old commission_percentage to all three carriers
        $existing = DB::table('carrier_commission_settings')->first();
        if ($existing && isset($existing->commission_percentage)) {
            DB::table('carrier_commission_settings')
                ->where('id', $existing->id)
                ->update([
                    'dhl_commission_percentage' => $existing->commission_percentage,
                    'fedex_commission_percentage' => $existing->commission_percentage,
                    'ups_commission_percentage' => $existing->commission_percentage,
                ]);
        } else {
            // If no existing record, ensure we have one with defaults
            DB::table('carrier_commission_settings')->updateOrInsert(
                ['id' => 1],
                [
                    'dhl_commission_percentage' => 15.00,
                    'fedex_commission_percentage' => 15.00,
                    'ups_commission_percentage' => 15.00,
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carrier_commission_settings', function (Blueprint $table) {
            // Drop per-carrier columns
            $table->dropColumn(['dhl_commission_percentage', 'fedex_commission_percentage', 'ups_commission_percentage']);
        });

        Schema::table('carrier_commission_settings', function (Blueprint $table) {
            // Restore the old single commission_percentage column
            $table->decimal('commission_percentage', 5, 2)->default(15.00)->after('id')->comment('Commission percentage applied to all carriers (DHL, FedEx, UPS)');
        });

        // Restore old data: use DHL commission as the default for all
        $existing = DB::table('carrier_commission_settings')->first();
        if ($existing) {
            DB::table('carrier_commission_settings')
                ->where('id', $existing->id)
                ->update([
                    'commission_percentage' => $existing->dhl_commission_percentage ?? 15.00,
                ]);
        }
    }
};
