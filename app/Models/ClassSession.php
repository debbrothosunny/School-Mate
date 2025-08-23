<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSession extends Model
{
     use HasFactory;

    protected $fillable = ['name', 'status'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }


    // Class Time Table 
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'session_id');
    }

    
}
