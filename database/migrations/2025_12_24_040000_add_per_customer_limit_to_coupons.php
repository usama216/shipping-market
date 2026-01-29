<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds per_customer_limit field to coupons table.
     * If set, limits how many times each customer can use the coupon.
     * If null, unlimited uses per customer.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'per_customer_limit')) {
                $table->unsignedInteger('per_customer_limit')->nullable()->after('usage_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('per_customer_limit');
        });
    }
};
