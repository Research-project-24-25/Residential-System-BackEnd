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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'other']);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('provider_cost', 10, 2)->default(0);
            $table->string('unit_of_measure')->nullable(); // e.g., 'hour', 'visit', 'month'
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence')->nullable(); // monthly, quarterly, yearly, null for one-time
            $table->softDeletes();
            $table->timestamps();

            // Index for performance
            $table->index('type');
            $table->index('is_recurring');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
