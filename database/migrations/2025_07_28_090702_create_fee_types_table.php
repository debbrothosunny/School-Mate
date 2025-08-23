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
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id(); // Auto-incrementing Primary Key
            $table->string('name')->unique(); // Unique name for the fee type (e.g., 'Tuition Fee')
            $table->text('description')->nullable(); // Optional detailed description
            $table->enum('frequency', ['monthly', 'biannual', 'annual']);
            
            // Changed from 'is_active' to 'status' with 0 for Active, 1 for Inactive
            $table->boolean('status')->default(0)->comment('0: Active, 1: Inactive'); 
            
            $table->timestamps(); // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
