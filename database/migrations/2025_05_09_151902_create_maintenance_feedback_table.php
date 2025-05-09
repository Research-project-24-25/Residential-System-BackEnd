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
        Schema::create('maintenance_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('resident_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->comment('Rating from 1 to 5');
            $table->text('comments')->nullable();
            $table->text('improvement_suggestions')->nullable();
            $table->boolean('resolved_satisfactorily')->default(true);
            $table->boolean('would_recommend')->default(true);
            $table->timestamps();

            // Ensure only one feedback per maintenance request
            $table->unique('maintenance_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_feedbacks');
    }
};
