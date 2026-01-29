<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loyalty_milestone_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "5 Shipments Reward"
            $table->string('milestone_type'); // 'shipment_count', 'referral_count', 'spend_amount'
            $table->integer('milestone_value'); // e.g., 5, 10, 20, 100
            $table->integer('points_reward');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['milestone_type', 'milestone_value']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_milestone_rules');
    }
};
