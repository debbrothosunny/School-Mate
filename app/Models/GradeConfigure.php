<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class GradeConfigure extends Model
{
    protected $table = 'grade_configures';

    
    use HasFactory;
    protected $fillable = [
        'class_interval',
        'letter_grade',
        'grade_point',
        'status', // Remember to add 'status'
    ];


     // Define casts for attribute types if needed (e.g., status as boolean)
    protected $casts = [
        'grade_point' => 'decimal:1', // Cast to decimal with 1 decimal place
        'status' => 'boolean',        // Cast status to boolean (0/1 -> false/true)
    ];
}
