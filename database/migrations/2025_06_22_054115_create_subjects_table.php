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
        $table->string('name')->unique();

        $table->unsignedInteger('full_marks')->comment('Total max marks (Subj + Obj + Pract).');
        $table->unsignedInteger('passing_marks')->comment('Total passing marks.');
        
        // ✅ NEW BREAKDOWN COLUMNS (Max Marks)
        $table->unsignedInteger('subjective_full_marks')->default(0);
        $table->unsignedInteger('objective_full_marks')->default(0);
        $table->unsignedInteger('practical_full_marks')->default(0); // 👈 ADDED PRACTICAL FULL MARKS
        
        // ✅ NEW BREAKDOWN COLUMNS (Passing Marks)
        $table->unsignedInteger('subjective_passing_marks')->default(0);
        $table->unsignedInteger('objective_passing_marks')->default(0);
        $table->unsignedInteger('practical_passing_marks')->default(0); // 👈 ADDED PRACTICAL PASSING MARKS

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
