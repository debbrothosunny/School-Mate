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
        // One Teacher has Many ClassNames (because class_names table has teacher_id)
        return $this->hasMany(ClassName::class);
    }

    
}