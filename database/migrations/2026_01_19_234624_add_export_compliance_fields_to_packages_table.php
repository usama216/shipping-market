<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Add export compliance fields required for DHL and international shipping.
     * These fields are used to generate commercial invoices automatically.
     */
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // Export compliance fields (required for commercial invoice generation)
            $table->string('incoterm', 10)->nullable()->after('note')->default('DAP'); // DAP, DDP, etc.
            $table->string('invoice_signature_name', 255)->nullable()->after('incoterm')->default('Authorized Shipper');
            $table->string('exporter_id_license', 50)->nullable()->after('invoice_signature_name')->default('EAR99');
            $table->string('us_filing_type', 50)->nullable()->after('exporter_id_license')->default('30.37(a) - Under $2,500');
            $table->string('exporter_code', 50)->nullable()->after('us_filing_type');
            $table->string('itn_number', 50)->nullable()->after('exporter_code'); // ITN if filing type requires it
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'incoterm',
                'invoice_signature_name',
                'exporter_id_license',
                'us_filing_type',
                'exporter_code',
                'itn_number',
            ]);
        });
    }
};
