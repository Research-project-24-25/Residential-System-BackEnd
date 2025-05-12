<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_service', function (Blueprint $table) {
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');

            $table->enum('billing_type', ['fixed', 'area_based', 'prepaid']);
            $table->decimal('price', 10, 2);
            $table->enum('status', ['active', 'inactive', 'pending_payment', 'expired'])->default('inactive');
            $table->json('details')->nullable();

            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_billed_at')->nullable();

            $table->timestamps();

            // Composite primary key
            $table->primary(['property_id', 'service_id']);

            // Add indexes for commonly queried columns
            $table->index('status');
            $table->index('billing_type');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_service');
    }
};
