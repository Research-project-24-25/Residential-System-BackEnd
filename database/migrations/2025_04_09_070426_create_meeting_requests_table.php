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
        Schema::create('meeting_requests', function (Blueprint $table) {
            $table->id();
            $table->string('property_type'); // 'apartment' or 'house'
            $table->unsignedBigInteger('property_id'); // ID of the apartment or house
            $table->string('user_name');
            $table->string('user_email');
            $table->string('user_phone')->nullable();
            $table->dateTime('preferred_time')->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'scheduled', 'cancelled', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_requests');
    }
};
