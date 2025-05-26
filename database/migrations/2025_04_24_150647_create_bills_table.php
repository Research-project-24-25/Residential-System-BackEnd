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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->string('bill_type'); // maintenance, water, electricity, gas, etc.
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'partially_paid', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->string('recurrence')->nullable(); // monthly, quarterly, yearly, one-time, etc.
            $table->date('next_billing_date')->nullable(); // For recurring bills
            $table->foreignId('created_by')->constrained('admins')->onDelete('restrict');
            $table->softDeletes();
            $table->timestamps();

            // Index for performance
            $table->index(['property_id', 'resident_id']);
            $table->index('due_date');
            $table->index('status');
            $table->index('bill_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
