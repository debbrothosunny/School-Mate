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
        Schema::create('grade_configures', function (Blueprint $table) {
            $table->id();
            $table->string('class_interval', 20); // Stores "80-100", "70-79", etc.
            $table->string('letter_grade', 5);    // A+, A, A-, B, C, D, F
            $table->decimal('grade_point', 3, 1); // 5.0, 4.0, 3.5, 3.0, 2.0, 1.0, 0.0
            $table->boolean('status')->default(0); // 0 = Active, 1 = Inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('grade_configures');
    }
};
