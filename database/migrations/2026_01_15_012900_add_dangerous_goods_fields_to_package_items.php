<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add UN code and dangerous goods class to package_items table
 * for DHL Express dangerous goods (VAS HE/HH) support
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            // UN hazmat code (e.g., UN3481 for lithium batteries)
            if (!Schema::hasColumn('package_items', 'un_code')) {
                $table->string('un_code', 10)->nullable()->after('is_dangerous');
            }

            // Dangerous goods class (e.g., 9 for lithium batteries, 3 for flammable liquids)
            if (!Schema::hasColumn('package_items', 'dangerous_goods_class')) {
                $table->string('dangerous_goods_class', 10)->nullable()->after('un_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('package_items', function (Blueprint $table) {
            if (Schema::hasColumn('package_items', 'un_code')) {
                $table->dropColumn('un_code');
            }
            if (Schema::hasColumn('package_items', 'dangerous_goods_class')) {
                $table->dropColumn('dangerous_goods_class');
            }
        });
    }
};
