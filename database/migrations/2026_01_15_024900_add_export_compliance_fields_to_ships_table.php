<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add export compliance fields to ships table for DHL and other carriers
     */
    public function up(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            // Export compliance fields - editable by operators for failed shipments
            $table->string('incoterm', 10)->nullable()->after('declared_value_currency')
                ->comment('Incoterm: DAP, DDU, DDP, etc.');
            $table->string('us_filing_type', 50)->nullable()->after('incoterm')
                ->comment('US export filing: 30.37(a), ITN number, etc.');
            $table->string('invoice_signature_name', 100)->nullable()->after('us_filing_type')
                ->comment('Name for invoice signature');
            $table->string('invoice_signature_title', 20)->nullable()->after('invoice_signature_name')
                ->comment('Title: Mr., Ms., Dr., etc.');
            $table->string('exporter_id', 50)->nullable()->after('invoice_signature_title')
                ->comment('Export license/EAR99');
            $table->string('exporter_code', 20)->nullable()->after('exporter_id')
                ->comment('Exporter code: EXPCZ, USHTS, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ships', function (Blueprint $table) {
            $table->dropColumn([
                'incoterm',
                'us_filing_type',
                'invoice_signature_name',
                'invoice_signature_title',
                'exporter_id',
                'exporter_code',
            ]);
        });
    }
};
