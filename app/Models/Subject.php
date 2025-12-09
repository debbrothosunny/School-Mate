<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use HasFactory;

    /**
     * Including all mark breakdown columns in $fillable for mass assignment.
     */
    protected $fillable = [
        'name',
        'full_marks',
        'passing_marks',
        
        // Mark Breakdown Columns
        'subjective_full_marks',
        'objective_full_marks',
        'practical_full_marks',
        'subjective_passing_marks',
        'objective_passing_marks',
        'practical_passing_marks',

        'status',
    ];

    /**
     * Get the teachers who have this as their primary subject.
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'subject_id');
    }

    /**
     * The classes that belong to this subject (Many-to-Many relationship).
     * The pivot table is 'class_subjects'.
     */
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(ClassName::class, 'class_subjects', 'subject_id', 'class_name_id');
    }
    
    /**
     * Get the marks records associated with this subject.
     */
    public function marks(): HasMany
    {
        return $this->hasMany(Mark::class);
    }
}
