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
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            // Foreign key for class_names table
            $table->foreignId('class_name_id')->constrained('class_names')->onDelete('cascade');
            // Foreign key for subjects table (assuming you have a subjects table)
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            // Foreign key for teachers table
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            // Foreign key for sessions table (assuming you have a sessions table)
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade');
            // Foreign key for sections table
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');

            // NEW: Foreign key for groups table
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('cascade'); // group_id can be nullable for classes without groups

            // Status field (e.g., 0 for inactive, 1 for active, or custom values)
            $table->boolean('status')->default(true); // Assuming true means active by default
            $table->timestamps();

            // Optional: Add unique constraint if a class can only have one subject per teacher, session, section, and group
            // If group_id is nullable, this unique constraint might need adjustment or a different approach
            // $table->unique(['class_name_id', 'subject_id', 'teacher_id', 'session_id', 'section_id', 'group_id'], 'unique_class_subject_assignment');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subjects');
    }
};
