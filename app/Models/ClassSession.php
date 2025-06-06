<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
     use HasFactory;

    protected $fillable = ['name', 'status'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
