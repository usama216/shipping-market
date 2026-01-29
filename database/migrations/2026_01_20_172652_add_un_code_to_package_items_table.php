<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add UN code (United Nations number) for dangerous goods classification.
     * Required when shipping dangerous goods/hazardous materials.
     */
    public function up(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->string('un_code', 10)->nullable()->after('is_dangerous');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            $table->dropColumn('un_code');
        });
    }
};
