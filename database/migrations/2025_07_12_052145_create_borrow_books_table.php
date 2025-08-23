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
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade'); // Assuming students table exists
            $table->date('borrow_date');
            $table->date('return_date')->nullable(); // Null if not yet returned
            $table->integer('status')->default('0'); // '(0)borrowed', '(1)returned', '(2)overdue', '(3)lost'

            // Optional: Add unique constraint to prevent a student from borrowing the same book multiple times simultaneously
            // This would require a more complex check if a student can borrow multiple copies of the same book
            // For simplicity, this unique constraint means one student can borrow one copy at a time.
            $table->unique(['book_id', 'student_id', 'return_date'], 'unique_borrow_per_student_book');
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
