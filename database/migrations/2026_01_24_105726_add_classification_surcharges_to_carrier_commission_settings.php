<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('carrier_commission_settings', function (Blueprint $table) {
            // Classification surcharges (applied per item that has the flag)
            $table->decimal('dangerous_goods_charge', 8, 2)->default(0.00)->after('ups_commission_percentage')->comment('Additional charge per dangerous item');
            $table->decimal('fragile_item_charge', 8, 2)->default(0.00)->after('dangerous_goods_charge')->comment('Additional charge per fragile item');
            $table->decimal('oversized_item_charge', 8, 2)->default(0.00)->after('fragile_item_charge')->comment('Additional charge per oversized item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carrier_commission_settings', function (Blueprint $table) {
            $table->dropColumn([
                'dangerous_goods_charge',
                'fragile_item_charge',
                'oversized_item_charge',
            ]);
        });
    }
};
