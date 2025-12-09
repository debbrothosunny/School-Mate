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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            // Nullable if a payment covers multiple invoices or is a general deposit (less common scenario)
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null'); 
            
            $table->integer('amount'); // Amount received (in smallest currency unit)
            $table->timestamp('payment_date')->useCurrent(); // When the payment was actually made/recorded
            $table->enum('method', ['cash', 'bank_transfer', 'mobile_banking', 'cheque', 'online_gateway'])->comment('Method of payment');
            
            $table->string('transaction_ref')->nullable()->unique()->comment('Reference number: Cheque No, bKash TXN ID, Bank Transfer Reference'); // Unique if applicable

            
            // Foreign key to users table to track who recorded the payment (ensure 'users' table exists)
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null'); 

            $table->timestamps();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
