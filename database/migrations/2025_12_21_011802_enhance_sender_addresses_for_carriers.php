<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add fields required by carrier APIs for sender/origin address.
     * Phone numbers and ISO country codes are mandatory for carrier submissions.
     */
    public function up(): void
    {
        Schema::table('sender_addresses', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('user_id');
            $table->string('full_name')->nullable()->after('company_name');
            $table->string('address_line_2')->nullable()->after('address');
            $table->string('phone_number', 20)->nullable()->after('address_line_2');
            $table->string('country_code', 3)->nullable()->after('country'); // ISO 3166-1 alpha-2/3
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sender_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'full_name',
                'address_line_2',
                'phone_number',
                'country_code'
            ]);
        });
    }
};
