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

            $table->integer('total_marks');
            $table->integer('passing_marks');
            $table->tinyInteger('status')->default(0)->comment('0=Active, 1=Inactive'); // Status column
            $table->timestamps();
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
