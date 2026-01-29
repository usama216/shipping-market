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
        Schema::create('ships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->string('tracking_number')->nullable();
            $table->double('total_weight')->default(0);
            $table->double('total_price')->default(0);
            $table->double('total_ship_payment')->default(0);
            $table->foreignId('user_address_id')
                ->nullable()
                ->constrained('user_addresses')
                ->onDelete('cascade');
            $table->json('international_shipping_option_id')->nullable();
            $table->json('packing_option_id')->nullable();
            $table->json('shipping_preference_option_id')->nullable();
            $table->string('national_id')->nullable();
            $table->double('handling_fee')->default(10);
            $table->double('subtotal')->default(0);
            $table->double('package_level_charges')->default(0);
            $table->double('estimated_shipping_charges')->default(0);
            $table->enum('status', ['pending', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('invoice_status', ['pending', 'paid', 'unpaid'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ships');
    }
};
