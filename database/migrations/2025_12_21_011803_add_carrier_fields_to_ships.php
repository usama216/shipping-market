<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add carrier response fields to store tracking numbers, labels, and API responses.
     */
    public function up(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            // Carrier response fields
            $table->string('carrier_tracking_number')->nullable()->after('tracking_number');
            $table->string('carrier_name')->nullable()->after('carrier_tracking_number'); // fedex, dhl, ups
            $table->string('carrier_service_type')->nullable()->after('carrier_name');
            $table->text('label_url')->nullable()->after('carrier_service_type');
            $table->longText('label_data')->nullable()->after('label_url'); // Base64 PDF
            $table->string('carrier_status')->default('pending')->after('label_data');
            $table->timestamp('submitted_to_carrier_at')->nullable()->after('carrier_status');
            $table->json('carrier_response')->nullable()->after('submitted_to_carrier_at'); // Full API response
            $table->json('carrier_errors')->nullable()->after('carrier_response'); // Error log
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            $table->dropColumn([
                'carrier_tracking_number',
                'carrier_name',
                'carrier_service_type',
                'label_url',
                'label_data',
                'carrier_status',
                'submitted_to_carrier_at',
                'carrier_response',
                'carrier_errors'
            ]);
        });
    }
};
