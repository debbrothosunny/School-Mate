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
         Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g., 'Mathematics', 'English', 'Physics', 'Chemistry'
            $table->string('code')->unique()->nullable(); // Optional: 'MATH101', 'ENG201'
            $table->unsignedInteger('full_marks');
            $table->unsignedInteger('passing_marks');
            $table->tinyInteger('status')->default(0)->comment('0=Active, 1=Inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
