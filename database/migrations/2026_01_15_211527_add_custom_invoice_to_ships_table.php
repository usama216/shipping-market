<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds custom invoice support for DHL Paperless Trade
     */
    public function up(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            // Toggle for using custom vs system-generated commercial invoice
            $table->boolean('use_custom_invoice')->default(false)->after('exporter_code');

            // Path to operator-uploaded custom invoice PDF
            $table->string('custom_invoice_path')->nullable()->after('use_custom_invoice');

            // Path to system-generated commercial invoice PDF
            $table->string('generated_invoice_path')->nullable()->after('custom_invoice_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            $table->dropColumn([
                'use_custom_invoice',
                'custom_invoice_path',
                'generated_invoice_path',
            ]);
        });
    }
};
