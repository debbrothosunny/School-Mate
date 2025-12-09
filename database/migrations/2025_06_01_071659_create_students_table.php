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
            $table->unsignedBigInteger('class_id');
            $table->foreign('class_id')->references('id')->on('class_names')->onDelete('cascade');
            $table->date('date_of_birth');
            $table->string('gender');
            $table->date('admission_date');
            $table->integer('age');
            $table->string('blood_group')->nullable();
            $table->unsignedBigInteger('session_id');
            $table->foreign('session_id')->references('id')->on('class_sessions')->onDelete('cascade');
            $table->foreignId('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->unsignedBigInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Link to users table

            // Existing student details
            $table->string('admission_number')->unique();
            $table->integer('roll_number');
            $table->string('parent_name');
            $table->string('address');
            $table->string('contact');
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0: Active (Enrolled), 1: Inactive (Left/Graduated)');
            $table->string('enrollment_status')->default('admitted')->comment('e.g., applied, under_review, admitted, enrolled, rejected, waitlisted');
    
            // Admission fee and payment details
            $table->integer('admission_fee_amount')->nullable(); 
            $table->boolean('admission_fee_paid')->default(false);    // Tracks if it's paid
            $table->string('payment_method')->nullable();             // e.g., 'Cash', 'bKash', 'Bank Transfer'             // e.g., 'Cash', 'bKash', 'Bank Transfer'

            $table->timestamps();
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
