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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            // Nullable if it's a custom item not linked to a specific fee_type (e.g., a special one-off penalty)
            $table->foreignId('fee_type_id')->nullable()->constrained('fee_types')->onDelete('set null'); 
            
            $table->string('description'); // e.g., "Tuition Fee - July 2025", "Annual Lab Fee", "Late Fee for July Tuition"
            $table->integer('amount'); // Amount for this specific item

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
