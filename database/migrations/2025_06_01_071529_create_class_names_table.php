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
        Schema::create('class_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->onDelete('cascade'); // Link to teachers table
            $table->foreignId('section_id')->constrained()->onDelete('cascade'); // Link to sections table
            $table->string('name'); // e.g., "Math Grade 10 A" or "Chemistry Lab"
            $table->string('class_time');
            $table->string('day'); // Added 'day' column for the class day(s)
            $table->string('room_number'); // Added 'room_number' column for the class room
            $table->tinyInteger('status')->default(0); //0 means active 1 means inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_names');
    }
};
