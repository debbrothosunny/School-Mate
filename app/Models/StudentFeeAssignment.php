<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFeeAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'fee_type_id',
        'class_id',   // <-- Added to fix the SQL error
        'section_id', // <-- Added to fix the SQL error
        'session_id', // <-- Added to fix the SQL error
        'status', 
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function class()
    {
        // Assuming your class table model is named ClassName
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function session()
    {
        // Assuming your session table model is named ClassSession
        return $this->belongsTo(ClassSession::class, 'session_id');
    }
}