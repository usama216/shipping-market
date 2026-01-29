<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Creates a separate customers table and migrates existing customer data.
     */
    public function up(): void
    {
        // Create customers table with same structure as users for customer-specific fields
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('avatar')->nullable();
            $table->longText('stripe_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('user_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->string('suite')->nullable()->unique(); // Customer suite number
            $table->string('country')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('tax_id')->nullable();
            $table->longText('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_old')->default(false);
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('email');
            $table->index('is_active');
        });

        // Add customer_id to related tables for future relations
        // These will be populated by the data migration seeder
        // Use hasColumn checks to prevent duplicate column errors
        if (!Schema::hasColumn('packages', 'customer_id')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('sender_id');
            });
        }

        if (!Schema::hasColumn('ships', 'customer_id')) {
            Schema::table('ships', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('transactions', 'customer_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('user_addresses', 'customer_id')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('user_cards', 'customer_id')) {
            Schema::table('user_cards', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('loyalty_transactions', 'customer_id')) {
            Schema::table('loyalty_transactions', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('coupon_usages', 'customer_id')) {
            Schema::table('coupon_usages', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('user_id');
            });
        }

        if (!Schema::hasColumn('package_change_requests', 'customer_id')) {
            Schema::table('package_change_requests', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove customer_id columns (with checks to prevent errors if column doesn't exist)
        if (Schema::hasColumn('packages', 'customer_id')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('ships', 'customer_id')) {
            Schema::table('ships', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('transactions', 'customer_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('user_addresses', 'customer_id')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('user_cards', 'customer_id')) {
            Schema::table('user_cards', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('loyalty_transactions', 'customer_id')) {
            Schema::table('loyalty_transactions', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('coupon_usages', 'customer_id')) {
            Schema::table('coupon_usages', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        if (Schema::hasColumn('package_change_requests', 'customer_id')) {
            Schema::table('package_change_requests', function (Blueprint $table) {
                $table->dropColumn('customer_id');
            });
        }

        Schema::dropIfExists('customers');
    }
};
