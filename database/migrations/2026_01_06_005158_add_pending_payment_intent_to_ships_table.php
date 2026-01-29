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
        Schema::table('ships', function (Blueprint $table) {
            $table->string('pending_payment_intent_id')->nullable()->after('status')
                ->comment('Stores PaymentIntent ID for PayPal redirect flow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            $table->dropColumn('pending_payment_intent_id');
        });
    }
};
