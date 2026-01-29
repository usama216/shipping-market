<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add dimension and weight unit fields required by carrier APIs.
     * Carriers calculate volumetric weight using L×W×H for pricing.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Dimensions (required for volumetric weight calculation)
            $table->decimal('length', 8, 2)->nullable()->after('weight');
            $table->decimal('width', 8, 2)->nullable()->after('length');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->enum('dimension_unit', ['in', 'cm'])->default('in')->after('height');
            $table->enum('weight_unit', ['lb', 'kg'])->default('lb')->after('weight');

            // Package classification for carrier-specific packaging
            $table->string('package_type')->default('YOUR_PACKAGING')->after('dimension_unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'length',
                'width',
                'height',
                'dimension_unit',
                'weight_unit',
                'package_type'
            ]);
        });
    }
};
