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
        Schema::create('class_fee_structures', function (Blueprint $table) {
            $table->id();

            // Foreign keys to your academic unit tables (ensure these tables exist)
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade'); // Nullable if not all classes have groups
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade'); // Nullable if not all classes have sections
    
             $table->unsignedBigInteger('amount')->nullable();
            // Foreign key to fee_types table
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');


            // Status of this fee structure template
            $table->boolean('status')->default(0)->comment('0: Active, 1: Inactive (for this fee structure template)'); 

            // Ensures a unique combination
            $table->unique(['class_id', 'session_id', 'group_id', 'section_id', 'fee_type_id'], 'unique_class_fee_template');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('class_fee_structures');
    }
};
