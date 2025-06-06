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
        'teacher_id',
        'section_id',
        'name', 
        'class_time',
        'day',
        'room_number',
        'status',
    ];

    // Relationship: A Class belongs to a Teacher
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
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


    
    
}
