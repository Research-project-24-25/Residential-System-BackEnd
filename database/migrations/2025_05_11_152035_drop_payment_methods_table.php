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
        Schema::dropIfExists('payment_methods');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // As per instruction, not implementing a rollback for this specific table drop.
        // If needed, the table schema can be recreated based on the original
        // 2025_04_24_141044_create_payment_methods_table.php migration.
    }
};
