<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_name_id',
        'subject_id',
        'teacher_id',
        'session_id', 
        'section_id',
        'group_id',
        'status',
    ];

    /**
     * Get the ClassName that owns the ClassSubject.
     */
    public function className(): BelongsTo
    {
        // A ClassSubject belongs to one ClassName
        return $this->belongsTo(ClassName::class, 'class_name_id');
    }

    /**
     * Get the Subject that owns the ClassSubject.
     */
    public function subject(): BelongsTo
    {
        // A ClassSubject belongs to one Subject
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the Teacher that is assigned to the ClassSubject.
     */
    public function teacher(): BelongsTo
    {
        // A ClassSubject belongs to one Teacher
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the Session that owns the ClassSubject.
     * Assuming your Session model is simply named 'Session' and maps to the 'sessions' table.
     */
    public function session(): BelongsTo
    {
        // A ClassSubject belongs to one Session
        return $this->belongsTo(ClassSession::class);
    }

    public function section(): BelongsTo
    {
        // A ClassSubject belongs to one Session
        return $this->belongsTo(Section::class);
    }

    public function group() // Define the new relationship
    {
        return $this->belongsTo(Group::class);
    }

    
}
