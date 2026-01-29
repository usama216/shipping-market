<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_id')->nullable();
            $table->string('tracking_id')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('special_request')->nullable();
            $table->string('date_received')->nullable();
            $table->string('from');
            $table->double('total_value')->default(0);
            $table->double('weight')->default(0);
            $table->longText('note')->nullable();
            $table->bigInteger('status')->default(1);
            $table->bigInteger('invoice_status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
