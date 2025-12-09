<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\FeeFrequency; // Make sure this Enum exists if you're using it

class FeeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'frequency',
        'status', // Corresponds to the 'status' column (0: Active, 1: Inactive)
    ];

    protected $casts = [
        'frequency' => FeeFrequency::class, // Casts the 'frequency' column to the FeeFrequency Enum
        'status' => 'boolean', // Laravel casts 0/1 to false/true automatically for boolean
    ];

    /**
     * Define relationship with ClassFeeStructure
     * A FeeType can be part of many ClassFeeStructures (templates).
    */
    public function classFeeStructures()
    {
        return $this->hasMany(ClassFeeStructure::class);
    }

    /**
     * Define relationship with StudentFeeAssignment
     * A FeeType can be assigned to many students.
     */
    public function studentFeeAssignments()
    {
        return $this->hasMany(StudentFeeAssignment::class);
    }

    /**
     * Define relationship with InvoiceItem
     * A FeeType can appear in many invoice items.
    */
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }


    

    
}