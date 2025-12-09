<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamResult extends Model
{
    use HasFactory; // Added for standard Laravel convention

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
        'final_letter_grade', 
        'overall_status',
        'subject_wise_data',
        'published_at',
    ]; 


    // Define casts for special columns
    protected $casts = [
        'published_at' => 'datetime',
        'subject_wise_data' => 'array', // Cast JSON data to a PHP array/object
        'percentage' => 'float',
        'final_grade_point' => 'float',
    ];


    // --- Relationships ---

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





    public function marks(): HasMany

    {

        return $this->hasMany(Mark::class, 'exam_id', 'exam_id')

                    ->where('student_id', $this->student_id)

                    ->with('subject');

    }


    // NOTE on marks() relationship:
    // The ExamResult is a SUMMARY. To get the underlying detail marks,
    // you should query the Mark model using the keys available on this result:
    // Example: Mark::where('exam_id', $this->exam_id)->where('student_id', $this->student_id)->get();
}
