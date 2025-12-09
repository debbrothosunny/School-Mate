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
      Schema::create('payroll_records', function (Blueprint $table) {
            $table->id();

            $table->morphs('salariable');

            $table->foreignId('salary_structure_id')
                  ->nullable()
                  ->constrained('salary_structures')
                  ->onDelete('set null');

            $table->unsignedSmallInteger('pay_month');
            $table->unsignedSmallInteger('pay_year');

            $table->unique(['salariable_id', 'salariable_type', 'pay_month', 'pay_year'], 'unique_payroll_period');

            // Earnings
            $table->unsignedInteger('gross_earning')
                  ->comment('Total salary components before any deductions.');

            // General Deduction
            $table->unsignedSmallInteger('deduction_percentage_used')->default(0);
            $table->unsignedInteger('deduction_amount')->default(0);

            // Absence Deduction
            $table->unsignedSmallInteger('absent_days')->default(0)
                  ->comment('Number of days absent in the pay period.');

            $table->unsignedInteger('absence_deduction_amount')->default(0)
                  ->comment('Amount deducted due to absences (calculated per institute policy).');

            // Final
            $table->unsignedInteger('total_deductions');
            $table->unsignedInteger('net_payable');

            $table->timestamp('payment_date')->nullable();

            $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('payroll_records');
    }
};
