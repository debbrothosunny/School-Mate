<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ClassName extends Model
{
     // Ensure the table name is correctly specified if the model name is unconventional
  protected $table = 'class_names';

    protected $fillable = [
        'class_name',
        'total_classes',
        'teacher_id',
        'status',
    ];



     // Relationship: A Class has many ClassTime entries
    public function classTimes(): HasMany
    {
        return $this->hasMany(ClassTime::class, 'class_name_id');
    }
    // Relationship: A Class belongs to a Teacher
    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'class_subjects', 'class_name_id', 'teacher_id')
                    ->withPivot('subject_id', 'session_id', 'section_id', 'status');
    }

    // Relationship: A Class belongs to a Section
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    // Relationship: A Class has many Students
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // Relationship: A Class has many Attendance records
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }


    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects', 'class_name_id', 'subject_id')
        ->withPivot('teacher_id', 'session_id', 'section_id', 'status');
    }



    /**
     * Get the class schedules for the class in teacher side dashboard.
    */
     public function classSchedules()
    {
        // Assuming a ClassTime model that belongs to a ClassName
        return $this->hasMany(ClassTime::class, 'class_name_id');
    }

    /**
     * Get the class schedules for the Exam in teacher side dashboard.
    */
    public function examSchedules()
    {
        // This is the missing relationship you need to add
        return $this->hasMany(ExamSchedule::class, 'class_id');
    }


    // For Assign Class Teacher
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
    
}
