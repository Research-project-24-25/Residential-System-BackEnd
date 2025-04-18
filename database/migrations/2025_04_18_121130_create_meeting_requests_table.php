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
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->datetime('requested_date');
            $table->text('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->string('id_document')->nullable(); // Path to stored ID document
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'completed'])->default('pending');
            $table->datetime('approved_date')->nullable(); // Date/time that admin approved/scheduled
            $table->foreignId('admin_id')->nullable()->constrained()->onDelete('set null'); // Admin who handled the request
            $table->text('admin_notes')->nullable(); // Notes from admin
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
