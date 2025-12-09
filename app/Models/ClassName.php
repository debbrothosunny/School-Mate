<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassName extends Model
{
    use HasFactory;

    protected $table = 'class_names';

    protected $fillable = [
        // Only columns remaining in the new migration are kept
        'class_name',
        'status',
    ];

    protected $casts = [
        // Removed academic_class_id, teacher_id, section_id, group_id
    ];

    // --- Relationships kept (They link to this model's ID) ---

    // Relationship: A Class has many ClassTime entries
    public function classTimes(): HasMany
    {
        return $this->hasMany(ClassTime::class, 'class_name_id');
    }

    // Relationship: A Class belongs to many Teachers (via pivot: class_subjects)
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(
            Teacher::class, 
            'class_subjects',
            'class_name_id',
            'teacher_id'
        )->withPivot('subject_id', 'session_id', 'section_id', 'status');
    }

    // Relationship: A Class has many Students (Students table should use class_name_id or class_id)
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // Relationship: A Class has many Attendance records
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    // Relationship: A Class has many Subjects (via pivot: class_subjects)
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_name_id', 'subject_id')
            ->withPivot('teacher_id', 'session_id', 'section_id', 'status');
    }

    public function classSchedules()
    {
        // This is a duplicate of classTimes(), but kept for completeness
        return $this->hasMany(ClassTime::class, 'class_name_id');
    }

    public function examSchedules()
    {
        return $this->hasMany(ExamSchedule::class, 'class_id');
    }
}