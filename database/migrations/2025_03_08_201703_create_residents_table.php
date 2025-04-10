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
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone_number');
            $table->integer('age');
            $table->enum('gender', ['male', 'female']);
            $table->enum('status', ['active', 'inactive'])->default('active');
            // Property association - either house or apartment
            $table->unsignedBigInteger('house_id')->nullable();
            $table->unsignedBigInteger('apartment_id')->nullable();
            $table->foreign('house_id')->references('id')->on('houses')->onDelete('set null');
            $table->foreign('apartment_id')->references('id')->on('apartments')->onDelete('set null');
            // Track which admin created this resident
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
