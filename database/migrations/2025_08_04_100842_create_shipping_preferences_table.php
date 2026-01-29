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
        Schema::create('shipping_preferences', function (Blueprint $table) {
            $table->id();
            // Note: customer_id foreign key is added later by 2025_12_23_181000 migration
            // after customers table is created
            $table->unsignedBigInteger('customer_id')->nullable();
            // Note: customer_address_id may not exist yet either
            $table->unsignedBigInteger('customer_address_id')->nullable();
            $table->string('preferred_ship_method')->nullable();
            $table->string('international_shipping_option')->nullable();
            $table->string('shipping_preference_option')->nullable();
            $table->string('packing_option')->nullable();
            $table->string('proforma_invoice_options')->nullable();
            $table->string('login_option')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('additional_email')->nullable();
            $table->string('maximum_weight_per_box')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_preferences');
    }
};

