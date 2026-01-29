<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            // Item-level dimensions for volumetric weight calculation
            $table->decimal('length', 8, 2)->nullable()->after('total_line_weight');
            $table->decimal('width', 8, 2)->nullable()->after('length');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->string('dimension_unit', 5)->default('in')->after('height');

            // Item classification flags
            $table->boolean('is_dangerous')->default(false)->after('dimension_unit');
            $table->boolean('is_fragile')->default(false)->after('is_dangerous');
            $table->boolean('is_oversized')->default(false)->after('is_fragile');
            $table->text('classification_notes')->nullable()->after('is_oversized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->dropColumn([
                'length',
                'width',
                'height',
                'dimension_unit',
                'is_dangerous',
                'is_fragile',
                'is_oversized',
                'classification_notes'
            ]);
        });
    }
};
