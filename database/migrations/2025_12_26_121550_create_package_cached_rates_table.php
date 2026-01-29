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
        Schema::create('package_cached_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->onDelete('cascade');
            $table->string('carrier', 20);              // fedex, dhl, ups
            $table->string('service_code', 50);         // FEDEX_GROUND, etc.
            $table->string('service_name', 100);
            $table->decimal('price', 10, 2);
            $table->integer('transit_days')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('origin_zip', 20)->nullable();
            $table->string('origin_country', 5)->default('US');
            $table->string('destination_key', 50);      // ZIP-country or city-country
            $table->string('destination_country', 5);
            $table->json('request_params')->nullable(); // For debugging
            $table->boolean('is_live_rate')->default(true);
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->index(['package_id', 'carrier']);
            $table->index(['package_id', 'destination_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_cached_rates');
    }
};
