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
        Schema::create('borrow_books', function (Blueprint $table) {
            $table->id();

            // Book Details
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            
            // Student Details (as input by the admin)
            $table->string('student_name', 255);
            $table->string('admission_number', 50)->index();
            $table->string('class_name', 50); // e.g., '10-A'

            // Borrow Details
            $table->unsignedSmallInteger('quantity')->default(1); // How many copies are borrowed
            $table->date('borrow_date');
            $table->date('due_date');
            $table->date('return_date')->nullable(); // Null until the book is returned
            
            // Status: 0=Borrowed, 1=Returned, 2=Overdue, 3=Cancelled/Lost
            $table->unsignedTinyInteger('status')->default(0); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_books');
    }
};
