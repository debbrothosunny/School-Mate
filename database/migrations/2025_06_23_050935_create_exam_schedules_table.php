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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade'); // Links to the Exam model
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade'); // Links to ClassName
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade'); // Links to Section
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade'); // Links to ClassSession
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->onDelete('set null'); // Teacher supervising (nullable, if teacher leaves)
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade'); // Subject of the exam

            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null'); // Foreign key to rooms table

            $table->date('exam_date'); // The specific date the exam is held
            $table->time('start_time'); // Start time of the exam
            $table->time('end_time');   // End time of the exam
            $table->string('day_of_week'); // Day of the week (e.g., MONDAY, TUESDAY)

            $table->tinyInteger('status')->default(0)->comment('0=Active, 1=Canceled, 2=Rescheduled'); // Status of the schedule entry

            $table->timestamps();

            // Composite unique constraints to prevent scheduling conflicts
            // 1. A room cannot be double-booked at the same time on the same date.
            $table->unique(['room_id', 'exam_date', 'start_time'], 'exam_schedule_room_time_unique');
            // 2. A teacher cannot be assigned to two exams at the same time on the same date.
            $table->unique(['teacher_id', 'exam_date', 'start_time'], 'exam_schedule_teacher_time_unique');
            // 3. A specific exam (for a given class/section/session/subject) cannot be scheduled multiple times for the same date and start time.
            $table->unique(['exam_id', 'class_id', 'section_id', 'session_id', 'subject_id', 'exam_date', 'start_time'], 'exam_schedule_exam_time_unique');
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
    }
};
