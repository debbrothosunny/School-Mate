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
            $table->string('class_name'); 
            $table->tinyInteger('status')->default(0); //0 means active 1 means inactive
            $table->unsignedSmallInteger('total_classes')->default(0);

            // Add the foreign key to link to the 'teachers' table
            $table->foreignId('teacher_id')
                  ->constrained() // This assumes the foreign key is named after the model (teacher) followed by _id.
                  ->onDelete('cascade');
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
