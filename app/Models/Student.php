<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo; 
use Illuminate\Database\Eloquent\Relations\HasMany;  

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class_id',
        'age',
        'parent_name',
        'session_id',
        'group_id',
        'section_id', 
        'image', 
        'address',
        'contact',
        'status',
    ];

    // Relationship: A Student belongs to a ClassName (e.g., 'Class 1')
    public function className(): BelongsTo
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    // Relationship: A Student belongs to a Session (assuming classSession is your Session model)
    public function session(): BelongsTo
    {
        return $this->belongsTo(classSession::class, 'session_id'); // Assuming 'classSession' is the model for sessions
    }

    // Relationship: A Student belongs to a Group
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    // Relationship: A Student belongs to a Section
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // Relationship: A Student has many Attendance records
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}