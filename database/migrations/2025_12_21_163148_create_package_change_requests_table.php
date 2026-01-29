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
        Schema::create('package_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');

            // Request type: 'package' for package details, 'address' for delivery address
            $table->enum('request_type', ['package', 'address'])->default('package');

            // Original values (JSON for flexibility)
            $table->json('original_values')->nullable();

            // Requested changes (JSON for flexibility)
            $table->json('requested_changes');

            // Customer notes explaining the change
            $table->text('customer_notes')->nullable();

            // Admin response notes
            $table->text('admin_notes')->nullable();

            // Status: pending, approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // Timestamps for review
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['package_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_change_requests');
    }
};
