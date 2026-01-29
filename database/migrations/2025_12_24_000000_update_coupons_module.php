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
        $driver = Schema::getConnection()->getDriverName();

        // Add new fields to coupons table
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'start_date')) {
                $table->dateTime('start_date')->nullable()->after('expiry_date');
            }
            if (!Schema::hasColumn('coupons', 'auto_apply')) {
                $table->boolean('auto_apply')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('coupons', 'target_audience')) {
                $table->string('target_audience')->default('all')->after('auto_apply'); // all, new_customer, registration
            }
            if (!Schema::hasColumn('coupons', 'is_private')) {
                $table->boolean('is_private')->default(false)->after('target_audience');
            }
        });

        // Update coupon_usages table
        Schema::table('coupon_usages', function (Blueprint $table) use ($driver) {
            if (!Schema::hasColumn('coupon_usages', 'customer_id')) {
                if ($driver === 'sqlite') {
                    // SQLite: Add without foreign key constraint
                    $table->foreignId('customer_id')->nullable()->after('user_id');
                } else {
                    // MySQL/PostgreSQL: Add with foreign key constraint
                    $table->foreignId('customer_id')->nullable()->after('user_id')->constrained('customers')->onDelete('cascade');
                }
            }

            // Make user_id nullable - skip on SQLite as it doesn't support ->change()
            if ($driver !== 'sqlite') {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'auto_apply', 'target_audience', 'is_private']);
        });

        Schema::table('coupon_usages', function (Blueprint $table) use ($driver) {
            if ($driver !== 'sqlite') {
                $table->dropForeign(['customer_id']);
            }
            $table->dropColumn('customer_id');
            // We can't easily revert user_id to not null without checking data, so we leave it nullable
        });
    }
};
