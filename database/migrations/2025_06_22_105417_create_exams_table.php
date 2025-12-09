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
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->string('exam_name');
            $table->foreignId('session_id')->constrained('class_sessions')->onDelete('cascade'); // Foreign key to class_sessions table

            // REMOVED: total_marks and passing_marks, as these are subject-specific, not exam-specific.

            $table->tinyInteger('status')->default(0)->comment('0=Active, 1=Inactive'); // Status column
            $table->timestamps();
            
            // Ensures an exam name is unique within a session
            $table->unique(['exam_name', 'session_id']);
        });
    }  

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
