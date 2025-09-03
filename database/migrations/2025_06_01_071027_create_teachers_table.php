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
        Schema::create('teachers', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Link to users table
            $table->string('name'); // Teacher Name
            $table->string('image')->nullable(); // Teacher Image (path)
            $table->string('subject_taught'); // Subject Taught
            $table->tinyInteger('status')->default(0); // 0 for  active and 1 inactive 
            $table->timestamps();

            // Optional: Add a unique constraint if each user can only be one teacher
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
