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
        Schema::create('marks', function (Blueprint $table) {
            $table->id();
            
            // --- Core Context ---
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('set null');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade'); 

            // --- Contextual Keys for Quick Querying ---
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');

            // --- Component Marks Obtained (Replaced old generic columns) ---
            $table->unsignedSmallInteger('subjective_marks')->nullable()->comment('Marks obtained in subjective part.');
            $table->unsignedSmallInteger('objective_marks')->nullable()->comment('Marks obtained in objective part.');
            $table->unsignedSmallInteger('practical_marks')->nullable()->comment('Marks obtained in practical part (if applicable).');

            // --- Result Summary for this Subject (Persisted/Cached Results) ---
            $table->unsignedSmallInteger('total_marks_obtained')->nullable()->comment('Sum of subjective, objective, practical');
            
            // ✅ NEW: Percentage marks
            $table->decimal('subject_percentage', 5, 2)->nullable()->comment('Percentage marks achieved in the subject.');
            // ✅ NEW: Letter Grade
            $table->string('subject_letter_grade', 5)->nullable()->comment('The corresponding letter grade (e.g., A+, B).');
            // ✅ NEW: Subject GPA
            $table->decimal('subject_grade_point', 3, 2)->nullable()->comment('The grade point (GPA) for the subject.');
            // ✅ NEW: Pass/Fail Status
            $table->string('subject_pass_status', 10)->nullable()->comment('Pass/Fail status for the subject (e.g., Pass, Fail).');

            $table->boolean('is_absent')->default(false)->comment('Flag if student was absent for this subject/exam.');

            $table->timestamps();

            // Unique constraint to ensure one set of marks per student/exam/subject/context
            $table->unique([
                'student_id',
                'exam_id',
                'subject_id',
                'class_id',
                'session_id',
                'section_id',
                'group_id',
            ], 'unique_marks_record');
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('marks');
    }
};
