<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassFeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'session_id',
        'group_id',
        'section_id',
        'fee_type_id',
        'amount', 
        'status', // Corresponds to the 'status' column
    ];

    protected $casts = [
        'status' => 'integer', // Ensures it's always an int in JSON
    ];

 
    public function className()
    {
        return $this->belongsTo(ClassName::class, 'class_id'); 
    }

    public function session()
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    /**
     * Get the effective amount for this class fee structure.
     * Uses amount_override if set, otherwise falls back to feeType's default_amount.
    */
    public function getEffectiveAmountAttribute()
    {
        return $this->amount_override ?? $this->feeType->default_amount;
    }

}