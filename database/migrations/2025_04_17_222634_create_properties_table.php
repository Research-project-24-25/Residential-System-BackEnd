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
            $table->string('identifier')->unique(); // B1F2A5, H21, V12, etc.
            $table->string('type'); // apartment, house, villa, etc.
            $table->decimal('price', 15, 2);
            $table->string('currency')->default('USD');
            $table->string('price_type')->default('sale'); // sale, rent, etc.
            $table->string('status')->default('available'); // available, sold, rented, etc.
            $table->text('description')->nullable();
            $table->integer('occupancy_limit')->default(0); // this unit can accommodate how many residents
            $table->integer('bedrooms')->default(0);
            $table->integer('bathrooms')->default(0);
            $table->integer('area')->default(0); // in square meters
            $table->json('images')->nullable(); // array of image URLs
            $table->json('features')->nullable(); // array of property features
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