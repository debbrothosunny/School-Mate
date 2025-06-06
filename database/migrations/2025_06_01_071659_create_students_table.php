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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('class_id'); // Can be null if assigned later
            $table->integer('age');
            $table->unsignedBigInteger('session_id');
            $table->foreignId('section_id')->constrained()->onDelete('cascade'); // Link to sections table
            $table->unsignedBigInteger('group_id');
            $table->string('parent_name');
            $table->string('address');
            $table->string('contact'); // Changed to string for phone numbers
            $table->string('image')->nullable(); // Added image column for student images
            $table->boolean('status')->default(0)->comment('0: Active, 1: Inactive'); // 0 for active and 1 inactive
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('class_id')->references('id')->on('class_names')->onDelete('cascade'); // Keeping onDelete('set null') for class_id as per your provided input
            $table->foreign('session_id')->references('id')->on('class_sessions')->onDelete('cascade'); // Changed to onDelete('cascade')
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade'); // Changed to onDelete('cascade')
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
