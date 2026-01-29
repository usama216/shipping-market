<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add customs declaration fields required for international shipping.
     * HS codes and country of origin are mandatory for customs clearance.
     */
    public function up(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            // Customs declaration fields (REQUIRED for international shipments)
            $table->string('hs_code', 12)->nullable()->after('description');
            $table->string('country_of_origin', 2)->nullable()->after('hs_code'); // ISO 3166-1 alpha-2
            $table->string('material')->nullable()->after('country_of_origin');
            $table->string('manufacturer')->nullable()->after('material');

            // Unit weight (carriers need per-item weight for customs)
            $table->decimal('weight_per_unit', 8, 3)->nullable()->after('value_per_unit');
            $table->enum('weight_unit', ['lb', 'kg', 'oz', 'g'])->default('lb')->after('weight_per_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->dropColumn([
                'hs_code',
                'country_of_origin',
                'material',
                'manufacturer',
                'weight_per_unit',
                'weight_unit'
            ]);
        });
    }
};
