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

            // --- NEW: Add foreign key for group_id ---
            // This links the timetable entry to the specific subject group (Science/Arts/None).
            // It must be nullable or default to the 'None' ID if classes below 9 don't require it. 
            // We will make it non-nullable here, relying on the 'None' group ID being used for non-group classes.
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade'); 

            // Timetable specific fields
            $table->string('day_of_week');

            $table->foreignId('class_time_slot_id')->constrained('class_time_slots')->onDelete('cascade');

            $table->foreignId('room_id')->nullable()->constrained('rooms')->onDelete('cascade');

            $table->tinyInteger('status')->default(0)->comment('0=Active, 1=Inactive');

            $table->timestamps();

            // --- UPDATED UNIQUE CONSTRAINTS (Constraint 1 MUST be updated) ---

            // Constraint 1: A specific class, section, AND group can only have one subject at a given time slot.
            // This is the essential change! For Class 9 (Science), this constraint prevents another subject being assigned to Class 9 (Science) at the same time.
            $table->unique(
                ['class_name_id', 'section_id', 'group_id', 'session_id', 'day_of_week', 'class_time_slot_id'],
                'unique_class_section_group_timeslot' // Updated name to reflect change
            );

            // Constraint 2: A teacher can only be assigned to one class at a given time slot. (No change needed here)
            $table->unique(
                ['teacher_id', 'session_id', 'day_of_week', 'class_time_slot_id'],
                'unique_teacher_timeslot'
            );

            // Constraint 3: A room can only be used by one class at a given time slot. (No change needed here)
            $table->unique(
                ['room_id', 'session_id', 'day_of_week', 'class_time_slot_id'],
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
