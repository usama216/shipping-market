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
        Schema::table('package_invoices', function (Blueprint $table) {
            // Invoice type: 'received' = from merchant, 'customer_submitted' = customer's declaration
            $table->enum('type', ['received', 'customer_submitted'])->default('received')->after('package_id');
            $table->string('invoice_number')->nullable()->after('type');
            $table->string('vendor_name')->nullable()->after('invoice_number');
            $table->date('invoice_date')->nullable()->after('vendor_name');
            $table->decimal('invoice_amount', 10, 2)->nullable()->after('invoice_date');
            $table->text('notes')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('package_invoices', function (Blueprint $table) {
            $table->dropColumn(['type', 'invoice_number', 'vendor_name', 'invoice_date', 'invoice_amount', 'notes']);
        });
    }
};
