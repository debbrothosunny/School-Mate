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
        'total_marks', 
        'passing_marks',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
    */
    protected $casts = [
        'status' => 'integer', // Cast status to integer for consistency (0 or 1)
    ];

    /**
     * Get the ClassSession that owns the Exam.
     * This defines the relationship where an Exam belongs to one ClassSession.
    */
    public function session(): BelongsTo
    {
        // Assuming your session model is named 'ClassSession' and the foreign key in 'exams' table is 'session_id'.
        return $this->belongsTo(ClassSession::class, 'session_id');
    }


    public function subjectsConfig(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_subject_config', 'exam_id', 'subject_id')
                    ->withPivot('full_marks_for_exam', 'passing_marks_for_exam')
                    ->withTimestamps();
    }

}
