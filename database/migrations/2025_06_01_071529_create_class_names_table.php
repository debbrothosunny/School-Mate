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

            // The table now solely focuses on defining the name of a class (e.g., 'Six', 'Seven', 'Eight').
            $table->string('class_name')->unique(); // Added unique constraint for the class name

            $table->tinyInteger('status')->default(0)->comment('0 = Active, 1 = Inactive');

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
