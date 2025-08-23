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
        $table->foreignId('student_id')->constrained()->onDelete('cascade');
        $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
        $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');
        $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
        $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
        $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade'); // Assuming you have an 'exams' table
        $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade'); // Assuming you have a 'subjects' table
        $table->integer('class_test_marks')->nullable(); // Marks from class tests
        $table->integer('assignment_marks')->nullable(); // Marks from assignments
        $table->integer('exam_marks')->nullable(); // Marks from exam
        $table->decimal('attendance_marks')->default(0); // This will be calculated
        $table->timestamps();

        // Unique constraint to ensure one marks record per student per exam per subject within a class, session, and section
        $table->unique([
            'student_id',
            'class_id',
            'session_id',
            'section_id',
            'exam_id',
            'group_id',
            'subject_id'
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
