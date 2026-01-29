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
        Schema::create('item_invoice_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_item_id')->constrained('package_items')->onDelete('cascade');
            $table->string('name')->nullable()->comment('Original filename');
            $table->string('file')->comment('Stored file path');
            $table->enum('file_type', ['image', 'pdf'])->default('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_invoice_files');
    }
};
