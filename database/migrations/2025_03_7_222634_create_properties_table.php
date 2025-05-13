<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropertiesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique(); // B1F2A5, H21, V12, etc.
            $table->enum('type', ['apartment', 'villa', 'house', 'studio']);
            $table->decimal('price', 15, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->enum('status', ['available_now', 'under_construction', 'sold', 'rented']);
            $table->text('description')->nullable();
            $table->integer('occupancy_limit')->default(0); // this unit can accommodate how many residents
            $table->integer('bedrooms')->default(0);
            $table->integer('bathrooms')->default(0);
            $table->integer('area')->default(0); // in square meters
            $table->json('images')->nullable(); // array of image URLs
            $table->json('features')->nullable(); // array of property features
            $table->decimal('acquisition_cost', 15, 2)->nullable();
            $table->date('acquisition_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
}
