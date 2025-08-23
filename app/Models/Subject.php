<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
        'full_marks',
        'passing_marks',
        'status',
    ];

    /**
     * Get the ClassSubjects that belong to this Subject.
    */
    

    /**
     * Get the teachers who have this as their primary subject.
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'subject_id');
    }


    public function classes()
    {
        return $this->belongsToMany(ClassName::class, 'class_subjects', 'subject_id', 'class_name_id');
    }
}
