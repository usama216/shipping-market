<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove package-level rate caching infrastructure.
 * 
 * Rates are now calculated at the shipment level when customers
 * create shipment requests, based on collective weights of all packages.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove rate columns from packages table
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['rate_fetch_status', 'rate_fetch_errors', 'rates_fetched_at']);
        });

        // Drop the package_cached_rates table entirely
        Schema::dropIfExists('package_cached_rates');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add rate columns to packages
        Schema::table('packages', function (Blueprint $table) {
            $table->string('rate_fetch_status', 20)->nullable()->after('status')
                ->comment('pending, fetching, success, failed, no_destination');
            $table->json('rate_fetch_errors')->nullable()->after('rate_fetch_status');
            $table->timestamp('rates_fetched_at')->nullable()->after('rate_fetch_errors');
        });

        // Recreate package_cached_rates table
        Schema::create('package_cached_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->string('carrier', 50);
            $table->string('service_code', 100);
            $table->string('service_name', 255);
            $table->decimal('price', 10, 2);
            $table->integer('transit_days')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('origin_zip', 20)->nullable();
            $table->string('origin_country', 2)->nullable();
            $table->string('destination_key', 100)->nullable();
            $table->string('destination_country', 2)->nullable();
            $table->json('request_params')->nullable();
            $table->boolean('is_live_rate')->default(false);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->index(['package_id', 'destination_key']);
            $table->index(['carrier', 'service_code']);
        });
    }
};
