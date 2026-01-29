<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add EEI (Electronic Export Information) code to package items.
     * Required for US export compliance and commercial invoice generation.
     */
    public function up(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->string('eei_code', 50)->nullable()->after('hs_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->dropColumn('eei_code');
        });
    }
};
