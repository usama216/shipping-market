<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('rate_fetch_status', 20)->nullable()->after('status')
                ->comment('pending, fetching, success, failed, no_destination');
            $table->json('rate_fetch_errors')->nullable()->after('rate_fetch_status');
            $table->timestamp('rates_fetched_at')->nullable()->after('rate_fetch_errors');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['rate_fetch_status', 'rate_fetch_errors', 'rates_fetched_at']);
        });
    }
};
