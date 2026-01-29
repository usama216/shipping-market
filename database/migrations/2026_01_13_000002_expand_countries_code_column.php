<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand the 'code' column in countries table to support longer codes.
 * 
 * The original column was 2 characters for ISO 3166-1 alpha-2 codes.
 * We now need to support internal codes like 'BQ-BO' for Caribbean Netherlands islands.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            // Expand code column from 2 to 10 characters for internal codes like BQ-BO
            $table->string('code', 10)->change();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('code', 2)->change();
        });
    }
};
