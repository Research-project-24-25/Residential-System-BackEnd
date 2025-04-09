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
        Schema::create('houses', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique(); // Unique identifier for the house within the compound
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('price_type')->default('sale'); // e.g., 'sale', 'rent_monthly'
            $table->string('status')->default('available'); // e.g., 'available', 'rented', 'sold', 'coming_soon'
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedInteger('area')->nullable(); // Internal living area (e.g., sq meters)
            $table->unsignedInteger('lot_size')->nullable(); // Total land area (e.g., sq meters)
            $table->string('property_style')->nullable(); // e.g., 'Villa', 'Bungalow', 'Townhouse'
            $table->json('images')->nullable();
            $table->json('features')->nullable();
            $table->timestamps();
        });
 
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
