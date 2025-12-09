<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exam extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_name',
        'session_id',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer', 
    ];

    /**
     * Get the ClassSession that owns the Exam.
     */
    public function session(): BelongsTo
    {
        // Assuming your session model is named 'ClassSession'
        return $this->belongsTo(ClassSession::class, 'session_id');
    }

    /**
     * Get the subjects configured for this specific exam, including 
     * the custom full and passing marks for this context.
     */
    public function subjectsConfig(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_subject_config', 'exam_id', 'subject_id')
                    // These pivot columns are critical for your flexible mark configuration
                    ->withPivot('full_marks_for_exam', 'passing_marks_for_exam')
                    ->withTimestamps();
    }
    
    /**
     * Get the individual marks records for this exam.
     */
    public function marks()
    {
        return $this->hasMany(Mark::class, 'exam_id');
    }
}
