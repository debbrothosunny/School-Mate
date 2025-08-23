<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model

{

    // Define the table name if it's not the plural form of the model name
    protected $table = 'exam_results';

    // Specify which attributes are mass assignable
   protected $fillable = [
        'exam_id',
        'student_id',
        'session_id',
        'class_id',
        'section_id',
        'group_id',
        'total_marks_obtained',
        'total_possible_marks',
        'percentage',
        'final_grade_point',
        'final_letter_grade', // <-- Make sure this is here!
        'overall_status',
        'subject_wise_data',
        'published_at',
    ];


     // Define cast for datetime columns
    protected $casts = [
        'published_at' => 'datetime',
        'subject_wise_data' => 'array',
    ];


    /**
     * Get the exam associated with the final exam result.
    */
    // Define relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function session()
    {
        return $this->belongsTo(ClassSession::class, 'session_id'); // Ensure foreign key
    }

    public function className()
    {
        return $this->belongsTo(ClassName::class, 'class_id'); // Ensure foreign key
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
