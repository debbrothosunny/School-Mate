<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayrollRecord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Polymorphic Keys
        'salariable_id',
        'salariable_type',

        // Links & Period
        'salary_structure_id',
        'pay_month',
        'pay_year',

        // Financial Fields
        'gross_earning',
        'deduction_percentage_used',
        'deduction_amount',
        'total_deductions',
        'net_payable',

        // Absence Tracking
        'absent_days',
        'absence_deduction_amount',
        'daily_rate_used', // nullable in DB

        // Payment
        'payment_date',
    ];

    /**
     * The attributes that should be cast.
    */
    protected $casts = [
        'pay_month'                => 'integer',
        'pay_year'                 => 'integer',
        'gross_earning'            => 'integer',
        'deduction_percentage_used'=> 'integer',
        'deduction_amount'         => 'integer',
        'total_deductions'         => 'integer',
        'net_payable'              => 'integer',
        'absent_days'              => 'integer',
        'absence_deduction_amount' => 'integer',
        'daily_rate_used'          => 'integer', // can be null
        'payment_date'             => 'datetime',
    ];

    /**
     * Get the staff member (Teacher, Accountant, etc.) that the payroll record belongs to.
    */
    public function salariable(): MorphTo
    {
        // Matches `salariable_id` and `salariable_type` columns
        return $this->morphTo();
    }

    /**
     * The salary structure that was used for the calculation.
    */
    public function salaryStructure(): BelongsTo
    {
        return $this->belongsTo(SalaryStructure::class);
    }
}