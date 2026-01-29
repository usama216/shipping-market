<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rate Markup Rules - Admin-configurable rate adjustments
 * 
 * Allows admins to apply percentage or fixed markups/discounts
 * on carrier rates based on various criteria.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('rate_markup_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 10, 2); // 10.00 = 10% or $10
            $table->string('carrier', 50)->nullable()->comment('null = all carriers');
            $table->string('service_code', 100)->nullable()->comment('null = all services');
            $table->decimal('min_weight', 8, 2)->nullable()->comment('Apply above weight (lbs)');
            $table->decimal('max_weight', 8, 2)->nullable()->comment('Apply below weight (lbs)');
            $table->string('destination_country', 2)->nullable()->comment('null = all countries');
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0)->comment('Higher = applied first');
            $table->timestamps();

            $table->index(['is_active', 'priority']);
            $table->index(['carrier', 'service_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_markup_rules');
    }
};
