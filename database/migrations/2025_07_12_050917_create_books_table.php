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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->date('publication_date')->nullable();
            $table->string('isbn')->unique()->nullable(); // International Standard Book Number
            $table->integer('quantity')->default(0); // Total number of copies
            $table->integer('available_quantity')->default(0); // Number of currently available copies
            $table->string('genre')->nullable(); // e.g., Fiction, Science, History
            $table->boolean('status')->default(0); // 0 for active, 1 for inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
