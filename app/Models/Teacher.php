<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Add this import
use Illuminate\Database\Eloquent\Relations\belongsToMany; // Add this import
use Illuminate\Database\Eloquent\Relations\HasMany;   // Add this import

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'image',
        'subject_taught',
        'status',
    ];

    // Relationship: A Teacher belongs to a User (for login)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: A Teacher can have many Classes
    public function classes(): HasMany
    {
        return $this->hasMany(ClassName::class, 'teacher_id'); // Use Class_ or SchoolClass if 'Class' causes issues
    }
   
        /**
     * Relationship: The classes this teacher is assigned to.
     * This is a many-to-many relationship.
     */
    public function classNames(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassName::class, 
            'class_subjects', // The pivot table name
            'teacher_id',     // The foreign key on the pivot table for this model (Teacher)
            'class_name_id'   // The foreign key on the pivot table for the related model (ClassName)
        )
        ->withPivot('subject_id', 'session_id', 'section_id', 'status');
    }


    /**
     * Get the class assigned to the teacher.
    */
    public function className()
    {
        return $this->hasOne(ClassName::class);
    }

    /**
     * Get the class schedules for the Exam in teacher side dashboard.
    */
    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class, 'teacher_id');
    }

    
    
}