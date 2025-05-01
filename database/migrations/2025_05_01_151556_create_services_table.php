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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['maintenance', 'utility', 'security', 'cleaning', 'other']);
            $table->decimal('base_price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('unit_of_measure')->nullable(); // e.g., 'hour', 'visit', 'month'
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence')->nullable(); // monthly, quarterly, yearly, null for one-time
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index for performance
            $table->index('type');
            $table->index('is_recurring');
            $table->index('is_active');
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->onDelete('restrict');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->date('requested_date');
            $table->date('scheduled_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained()->onDelete('set null'); // Admin who handled the request
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            $table->foreignId('bill_id')->nullable()->constrained()->onDelete('set null'); // Reference to the billing entry
            $table->timestamps();

            // Index for performance
            $table->index(['property_id', 'resident_id']);
            $table->index('service_id');
            $table->index('status');
            $table->index('requested_date');
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('services');
    }
};
