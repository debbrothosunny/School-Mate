<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // Add this import

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status', 
    ];

    // Relationship: A Section can have many Classes
    public function classes(): HasMany
    {
        return $this->hasMany(ClassName::class, 'section_id'); // Use Class_
    }
}