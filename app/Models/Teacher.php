<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Add this import
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
   
    public function classNames()
    {
        // The 'class_subjects' table is the pivot table.
        // 'teacher_id' is the foreign key on the pivot table for this model.
        // 'class_name_id' is the foreign key on the pivot table for the related model (ClassName).
        return $this->belongsToMany(ClassName::class, 'class_subjects', 'teacher_id', 'class_name_id')
                    ->withPivot('subject_id', 'session_id', 'section_id', 'status'); // Include pivot data if you need it
    }


    /**
     * Get the class assigned to the teacher.
     */
    public function className()
    {
        return $this->hasOne(ClassName::class);
    }

    
    
}