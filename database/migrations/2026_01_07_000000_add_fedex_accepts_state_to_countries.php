<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add FedEx-specific state handling flag to countries table.
 * 
 * FedEx address validation is carrier-specific and unforgiving.
 * Many Caribbean countries have postal codes but FedEx rejects state/province values.
 * This flag controls whether state should be sent to FedEx API.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            // FedEx-specific: whether to send state/province code
            // Default true (most countries accept state)
            // Set false for Caribbean territories where FedEx rejects state values
            $table->boolean('fedex_accepts_state')->default(true)->after('has_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('fedex_accepts_state');
        });
    }
};
