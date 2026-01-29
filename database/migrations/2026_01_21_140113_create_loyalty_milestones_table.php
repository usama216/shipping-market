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
        Schema::create('loyalty_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('milestone_type'); // 'shipment_count', 'referral_count', 'spend_amount', etc.
            $table->integer('milestone_value'); // e.g., 5, 10, 20, 100 for shipment counts
            $table->integer('points_awarded');
            $table->timestamp('achieved_at');
            $table->timestamps();

            $table->index(['customer_id', 'milestone_type']);
            $table->unique(['customer_id', 'milestone_type', 'milestone_value'], 'loyalty_milestones_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_milestones');
    }
};
