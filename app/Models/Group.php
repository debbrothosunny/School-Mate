<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
     use HasFactory;
     public const GROUP_TYPES = [
        'Science',
        'Arts',
        'Commerce',
        'None', // For classes that don't have specific academic groups
    ];
    protected $fillable = ['name', 'status'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
