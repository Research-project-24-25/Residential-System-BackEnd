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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->string('type'); // credit_card, bank_transfer, cash, etc.
            $table->string('provider')->nullable(); // visa, mastercard, bank name, etc.
            $table->string('account_number')->nullable();
            $table->string('last_four')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('cardholder_name')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->enum('status', ['active', 'inactive', 'expired', 'cancelled'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Index for performance
            $table->index('resident_id');
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
