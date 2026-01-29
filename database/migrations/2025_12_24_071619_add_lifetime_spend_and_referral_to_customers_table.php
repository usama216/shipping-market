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

        Schema::table('customers', function (Blueprint $table) use ($driver) {
            // Add lifetime_spend if it doesn't exist
            if (!Schema::hasColumn('customers', 'lifetime_spend')) {
                $table->decimal('lifetime_spend', 12, 2)->default(0)->after('loyalty_points');
            }

            // Add referral_code if it doesn't exist
            if (!Schema::hasColumn('customers', 'referral_code')) {
                $table->string('referral_code', 10)->unique()->nullable()->after('lifetime_spend');
            }

            // Add referred_by_id if it doesn't exist
            if (!Schema::hasColumn('customers', 'referred_by_id')) {
                if ($driver === 'sqlite') {
                    // SQLite: Add without foreign key constraint
                    $table->unsignedBigInteger('referred_by_id')->nullable()->after('referral_code');
                } else {
                    // MySQL/PostgreSQL: Add with foreign key constraint
                    $table->foreignId('referred_by_id')->nullable()->after('referral_code')
                        ->constrained('customers')->nullOnDelete();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        Schema::table('customers', function (Blueprint $table) use ($driver) {
            if (Schema::hasColumn('customers', 'referred_by_id')) {
                if ($driver !== 'sqlite') {
                    $table->dropForeign(['referred_by_id']);
                }
                $table->dropColumn('referred_by_id');
            }
            if (Schema::hasColumn('customers', 'referral_code')) {
                $table->dropColumn('referral_code');
            }
            if (Schema::hasColumn('customers', 'lifetime_spend')) {
                $table->dropColumn('lifetime_spend');
            }
        });
    }
};
