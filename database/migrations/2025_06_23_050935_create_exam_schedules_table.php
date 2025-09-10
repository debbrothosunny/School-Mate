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
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->onDelete('set null');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('set null');

            // NEW: Add a foreign key to the exam_time_slots table
            $table->foreignId('exam_slot_id')->constrained('exam_time_slots')->onDelete('cascade');

            $table->date('exam_date');
            $table->string('day_of_week');
            $table->tinyInteger('status')->default(0)->comment('0=Active, 1=Canceled, 2=Rescheduled');

            $table->timestamps();

            // Updated composite unique constraints to prevent scheduling conflicts
            // 1. A room cannot be double-booked at the same time on the same date.
            $table->unique(['room_id', 'exam_date', 'exam_slot_id'], 'exam_schedule_room_time_unique');
            // 2. A teacher cannot be assigned to two exams at the same time on the same date.
            $table->unique(['teacher_id', 'exam_date', 'exam_slot_id'], 'exam_schedule_teacher_time_unique');
            // 3. A specific exam cannot be scheduled multiple times for the same date and time slot.
            $table->unique(['exam_id', 'class_id', 'section_id', 'session_id', 'subject_id', 'exam_date', 'exam_slot_id'], 'exam_schedule_exam_time_unique');
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
