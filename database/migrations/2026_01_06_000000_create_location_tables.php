<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates location lookup tables for cascading address dropdowns.
 * 
 * Structure:
 * - countries: Caribbean islands/territories (replaces countries.json)
 * - states: Parishes, provinces, districts, or islands within a country
 * - cities: Towns/cities within each state, with optional postal codes
 */
return new class extends Migration {
    public function up(): void
    {
        // Countries table - replaces countries.json
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Full country name
            $table->string('code', 2)->unique();       // ISO 3166-1 alpha-2 (e.g., JM, TT, BB)
            $table->string('phone_prefix', 10)->nullable(); // e.g., +1-876 for Jamaica
            $table->boolean('has_postal_code')->default(false); // Whether country uses postal codes
            $table->string('postal_code_format')->nullable();   // Regex or format hint (e.g., "BBXXXXX")
            $table->boolean('is_active')->default(true);        // For enabling/disabling territories
            $table->integer('sort_order')->default(0);          // For custom ordering in dropdown
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });

        // States/Parishes/Districts table
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('name');           // State/parish/district name
            $table->string('code', 10)->nullable(); // Abbreviation (e.g., "KN" for Kingston)
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['country_id', 'sort_order']);
        });

        // Cities/Towns table
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained()->onDelete('cascade');
            $table->string('name');                     // City/town name
            $table->string('postal_code', 20)->nullable(); // Postal/zip code if available
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['state_id', 'sort_order']);
            $table->index('postal_code'); // For lookup by postal code if needed
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};
