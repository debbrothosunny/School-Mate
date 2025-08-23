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
        Schema::create('class_times', function (Blueprint $table) {
            $table->id();

            // Foreign keys linking to other entities
            $table->foreignId('class_name_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');

            // Timetable specific fields
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');


            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');

            $table->tinyInteger('status')->default(0)->comment('0=Active, 1=Inactive');

            $table->timestamps();

            // --- CHANGE HERE: Update unique constraints to use room_id ---
            // Constraint 1: A specific class and section can only have one subject at a given time slot.
            $table->unique(
                ['class_name_id', 'section_id', 'session_id', 'day_of_week', 'start_time'],
                'unique_class_section_timeslot'
            );

            // Constraint 2: A teacher can only be assigned to one class at a given time slot.
            $table->unique(
                ['teacher_id', 'session_id', 'day_of_week', 'start_time'],
                'unique_teacher_timeslot'
            );

            // Constraint 3: A room can only be used by one class at a given time slot.
            // Note: Unique constraints on nullable columns allow multiple NULL values.
            $table->unique(
                ['room_id', 'session_id', 'day_of_week', 'start_time'],
                'unique_room_timeslot'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_times');
    }
};
