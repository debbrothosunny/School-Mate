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
            // Links to the specific exam schedule entry
            $table->foreignId('exam_schedule_id')->constrained('exam_schedules')->onDelete('cascade');
            // Links to the student being assigned a seat
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');

            // The simplified seat number (e.g., 1, 2, 3... up to the room's capacity)
            $table->integer('seat_number')->comment('The sequential seat number assigned to the student within the exam room.');

            $table->timestamps();

            // Composite unique constraints:
            // 1. A student can only be assigned one seat for a given exam schedule.
            $table->unique(['exam_schedule_id', 'student_id'], 'unique_exam_student_assignment');
            // 2. A specific seat number within a room can only be assigned to one student for a given exam schedule.
            $table->unique(['exam_schedule_id', 'seat_number'], 'unique_exam_seat_number_per_schedule');
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
