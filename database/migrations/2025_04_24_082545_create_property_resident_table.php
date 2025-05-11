<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_resident', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resident_id')->constrained()->cascadeOnDelete();

            $table->enum('relationship_type', ['buyer', 'co_buyer', 'renter']);
            $table->decimal('sale_price', 14, 2)->nullable(); // buyers
            $table->decimal('ownership_share', 5, 2)->nullable(); // co‑buyers (%
            $table->decimal('monthly_rent', 12, 2)->nullable(); // renters
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_resident');
    }
};
