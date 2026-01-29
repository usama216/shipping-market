<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Create shipment_events table for tracking timeline history.
     */
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ship_id')->constrained('ships')->onDelete('cascade');
            $table->string('status'); // pending, picked_up, in_transit, out_for_delivery, delivered, exception
            $table->string('description')->nullable();
            $table->string('location')->nullable(); // City, State or facility name
            $table->string('source')->default('system'); // system, fedex, dhl, ups
            $table->timestamp('event_time')->nullable(); // When the event occurred
            $table->json('raw_data')->nullable(); // Store original carrier response
            $table->timestamps();

            // Index for faster queries
            $table->index(['ship_id', 'event_time']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};
