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
        Schema::create('student_fee_assignments', function (Blueprint $table) {
            $table->id();

            // Foreign keys to link to students and fee_types (ensure 'students' table exists)
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');

   
            // Validity period for this fee assignment
            $table->date('applies_from')->comment('The date from which this fee assignment is valid for the student.');
            $table->date('applies_to')->nullable()->comment('The date until which this fee assignment is valid. Nullable for indefinite or one-time fees.');

            // Status of this particular assignment
            $table->boolean('status')->default(0)->comment('0: Active, 1: Inactive (for this specific assignment)');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('student_fee_assignments');
    }
};
