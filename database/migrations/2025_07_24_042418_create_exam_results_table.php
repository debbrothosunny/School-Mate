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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();

            // Core Relationships
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('set null');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            // Contextual Foreign Keys (Kept for quick querying)
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade'); 

            // Calculated Totals and Grades
            $table->unsignedSmallInteger('total_marks_obtained')->nullable()->comment('Sum of marks from all subjects for this student in this exam.');
            $table->unsignedSmallInteger('total_possible_marks')->nullable()->comment('Sum of full marks from all subjects for this student in this exam.');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('final_grade_point', 3, 2)->comment('Calculated GPA/Grade Point');
            $table->string('final_letter_grade', 10);
            $table->string('overall_status', 20)->nullable()->comment('e.g., Pass, Fail, Promoted');
            
            // Detailed breakdown (e.g., subject-wise status, marks, grades in JSON format)
            $table->json('subject_wise_data')->nullable()->comment('Detailed breakdown per subject, including component marks (Subjective/Objective/Practical).');
            
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Ensures only one summary result per student per exam.
            $table->unique(['exam_id', 'student_id']); 
        });
    }
  
    /**0.
     * 
     * 
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
