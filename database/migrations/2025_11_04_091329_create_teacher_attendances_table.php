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
        Schema::create('teacher_attendances', function (Blueprint $table) {
            $table->id();

            // Link to the teachers table
            $table->foreignId('teacher_id')
                  ->constrained() // Assumes the foreign table is 'teachers'
                  ->onDelete('cascade'); 

            $table->date('date'); // The specific date of the attendance

            // Status of the attendance record
            $table->enum('status', [
                'Present',
                'Absent',
                'Leave',
                'Half Day'
            ])->default('Absent');

            $table->time('in_time')->nullable(); // Check-in time
            $table->time('out_time')->nullable(); // Check-out time
            $table->text('note')->nullable(); // Optional: reason for absence or leave

            $table->timestamps(); // Adds created_at and updated_at

            // Enforce that a teacher can only have one attendance record per day
            $table->unique(['teacher_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_attendances');
    }
};
