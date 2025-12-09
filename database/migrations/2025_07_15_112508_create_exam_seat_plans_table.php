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
        Schema::create('exam_seat_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('class_names')->onDelete('cascade');
            $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('class_sessions')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade'); // Added group_id
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->integer('seat_number')->comment('The sequential seat number assigned to the student within the exam room.');
            $table->timestamps();

            $table->unique(
                ['exam_id', 'class_id', 'section_id', 'session_id', 'group_id', 'student_id'],
                'unique_exam_class_section_session_group_student_assignment'
            );
            $table->unique(
                ['exam_id', 'class_id', 'section_id', 'session_id', 'group_id', 'room_id', 'seat_number'],
                'unique_exam_class_section_session_group_seat_number'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_seat_plans');
    }
};
