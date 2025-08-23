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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('class_id')->constrained('class_names')->onDelete('cascade');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade'); // Added session_id
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade'); // Added section_id
            $table->foreignId('group_id')->constrained('groups')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late'])->default('present');
            $table->timestamps();

            // Custom, shorter unique constraint name to avoid 'too long' error
            // This ensures a student has only one attendance record per class, per session, per section, per group, per day.
            $table->unique(['student_id', 'class_id', 'session_id', 'section_id', 'group_id', 'date'], 'unique_attendance_record');
        });

    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
