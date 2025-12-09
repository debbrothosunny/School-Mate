<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SalaryStructure extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Polymorphic Keys
        'salariable_id',
        'salariable_type',
        
        // Reference Data
        'designation_name',
        'effective_date',

        // Salary Components (MUST match the keys in your migration)
        'basic_salary',
        'house_rent_allowance',
        'medical_allowance',
        'academic_allowance',
        'transport_allowance',
        'deduction_percentage', // Corrected to match the new schema
        'festival_bonus',
    ];
    
    /**
     * Get the staff member (User or Teacher) that owns the salary structure.
     */
    public function salariable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Helper to calculate the structure's monthly gross salary (base for deductions).
     */
    public function getMonthlyGrossAttribute(): int
    {
        return $this->basic_salary 
             + $this->house_rent_allowance 
             + $this->medical_allowance 
             + $this->academic_allowance 
             + $this->transport_allowance;
    }
}
