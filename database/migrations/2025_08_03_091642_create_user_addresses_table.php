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
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('address_name');
            $table->string('full_name');
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('country');
            $table->string('state')->nullable();
            $table->string('city');
            $table->string('postal_code');
            $table->string('country_code');
            $table->string('phone_number');
            $table->boolean('is_default_us')->default(false);
            $table->boolean('is_default_uk')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
