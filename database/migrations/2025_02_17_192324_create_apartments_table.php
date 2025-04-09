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
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('floor_id')->constrained('floors')->cascadeOnDelete();
            $table->string('number'); // Apartment identifier (e.g., "101", "Apt 3B")
            $table->decimal('price', 10, 2)->nullable(); // Example: 10 total digits, 2 decimal places
            $table->string('currency', 3)->default('USD'); // 3-letter currency code
            $table->string('price_type')->default('sale'); // e.g., 'sale', 'rent_monthly'
            $table->string('status')->default('available'); // e.g., 'available', 'rented', 'sold', 'coming_soon'
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedInteger('area')->nullable(); // Assuming square meters or feet, whole numbers
            $table->json('images')->nullable(); // Store image paths/data as JSON array
            $table->json('features')->nullable(); // Store features as JSON array or object
            $table->timestamps();

            $table->unique(['floor_id', 'number']); // Ensure apartment number is unique within the floor
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
