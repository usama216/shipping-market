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
        Schema::create('shipping_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('service')->nullable();
            $table->string('type')->nullable();
            $table->string('range_value')->nullable();
            $table->string('range_to')->nullable();
            $table->string('price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_pricing');
    }
};
