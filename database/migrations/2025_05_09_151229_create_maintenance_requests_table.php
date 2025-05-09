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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->text('issue_details')->nullable();
            $table->json('images')->nullable(); // Store multiple image paths
            $table->enum('priority', ['low', 'medium', 'high', 'emergency'])->default('medium');
            $table->enum('status', ['pending', 'approved', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('requested_date');
            $table->date('scheduled_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            $table->foreignId('bill_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('has_feedback')->default(false);
            $table->timestamps();

            // Index for performance
            $table->index(['property_id', 'resident_id']);
            $table->index('maintenance_id');
            $table->index('status');
            $table->index('priority');
            $table->index('requested_date');
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
