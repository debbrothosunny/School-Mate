<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Add this import

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'session_id', 
        'section_id',
        'group_id',
        'date',
        'status',
    ];

    protected $casts = [
        'date' => 'date', // Cast date attribute to Carbon instance
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function session(): BelongsTo // NEW: Relationship for session
    {
        return $this->belongsTo(ClassSession::class, 'session_id'); 
    }

    public function section(): BelongsTo // NEW: Relationship for section
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}