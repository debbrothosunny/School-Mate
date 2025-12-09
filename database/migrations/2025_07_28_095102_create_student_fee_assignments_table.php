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

            // Foreign keys
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');

            // Status of this particular assignment
            $table->boolean('status')->default(0)->comment('0: Active, 1: Inactive');

            $table->timestamps();

            // Unique constraint to prevent duplicates
            $table->unique(
                ['student_id', 'fee_type_id', 'class_id', 'section_id', 'session_id'],
                'unique_fee_assignment'
            );
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
