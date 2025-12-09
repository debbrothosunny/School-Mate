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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('invoice_number')->unique(); // Unique identifier for the invoice
            $table->string('billing_period')->nullable()->comment('e.g., "2025-07" for monthly, "2025-2026 Annual"');
            
            $table->date('due_date');
            $table->integer('total_amount_due'); // Sum of all invoice_items amounts for this invoice
            $table->integer('amount_paid')->default(0); // Total amount received against this invoice
            $table->integer('balance_due'); // Calculated: total_amount_due - amount_paid

            // Status of the invoice
            $table->enum('status', ['pending', 'partially_paid', 'paid'])->default('pending');
            
            $table->timestamp('issued_at')->useCurrent(); // When the invoice was created/issued
            $table->timestamp(column: 'paid_at')->nullable(); // When the invoice was fully paid

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
