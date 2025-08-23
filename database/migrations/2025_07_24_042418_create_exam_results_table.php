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
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->integer('total_marks_obtained')->nullable(); // Sum of all subject marks for this student in this exam
            $table->integer('total_possible_marks')->nullable(); // Sum of total_marks from all subjects for this student in this exam
            $table->decimal('percentage', 5, 2)->nullable(); // Overall percentage for the exam
            $table->decimal('final_grade_point', 3, 2); // Overall GPA for the exam
            $table->string('final_letter_grade', 10); // Overall letter grade for the exam
            $table->string('overall_status', 20)->nullable(); // e.g., 'Pass', 'Fail'
            $table->json('subject_wise_data')->nullable();
            $table->timestamp('published_at')->nullable(); // When the result was officially published
            $table->timestamps();

            // Unique constraint to ensure only one final result per student per exam
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
