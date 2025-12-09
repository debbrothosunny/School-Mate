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
        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();

            // 1. Employee Identification (Polymorphic Relation)
            $table->morphs('salariable'); 

            // 2. Core Salary Components (Using unsignedInteger as requested)
            $table->unsignedInteger('basic_salary')->default(0);
            $table->unsignedInteger('house_rent_allowance')->default(0);
            $table->unsignedInteger('medical_allowance')->default(0);
            
            // 3. Allowance
            $table->unsignedInteger('academic_allowance')->default(0);
            $table->unsignedInteger('transport_allowance')->default(0); 
            
            // 4. General Deduction Configuration (RE-ADDED for school-specific percentage)
            // This allows the deduction rate (10%, 20%, 25%) to be set per salary structure.
            $table->unsignedSmallInteger('deduction_percentage')->default(0)
                  ->comment('General salary deduction percentage based on school/structure policy (e.g., 10, 20, 25).');

            $table->unsignedInteger('festival_bonus')->default(0);
            
            // 5. Link to the Staff Member's Designation 
            $table->string('designation_name')->nullable(); 

            // 6. Effective Date (When this structure starts)
            $table->date('effective_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};
