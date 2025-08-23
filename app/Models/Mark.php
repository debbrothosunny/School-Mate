<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'session_id',
        'section_id',
        'group_id',
        'exam_id',
        'subject_id',
        'class_test_marks',
        'assignment_marks',
        'exam_marks',
        'attendance_marks',
    ];

    // Define relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id'); // Assuming ClassName model
    }

    public function session()
    {
        return $this->belongsTo(ClassSession::class, 'session_id'); // Assuming ClassSession model
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }


    public function classSubjectDetail()
    {
        // Adjust foreign keys if your class_subjects table uses different names
        return $this->belongsTo(ClassSubject::class, 'subject_id', 'subject_id')
                    ->where('class_name_id', $this->class_id)
                    ->where('session_id', $this->session_id);
                    // Add section_id and group_id if your class_subjects pivot
                    // also filters by these to find the exact total_subject_marks
                    // ->where('section_id', $this->section_id);
                    // ->where('group_id', $this->group_id);
    }

    // --- Accessors for Mark Calculations ---

    /**
     * Get the total marks obtained for this subject.
     * Combines class test, assignment, exam, and attendance marks.
     * @return int
     */
    public function getTotalSubjectMarksObtainedAttribute()
    {
        // Ensure that nullable fields default to 0 if not set
        $classTest = $this->class_test_marks ?? 0;
        $assignment = $this->assignment_marks ?? 0;
        $exam = $this->exam_marks ?? 0;
        $attendance = $this->attendance_marks ?? 0; // Assuming attendance_marks is already numeric

        return $classTest + $assignment + $exam + $attendance;
    }

    /**
     * Get the percentage obtained for this subject.
     * Requires the related Exam model to have 'total_marks'.
     * @return float
     */
    public function getSubjectPercentageAttribute()
    {
        if ($this->relationLoaded('exam') && $this->exam && $this->exam->total_marks > 0) {
            $totalObtained = $this->total_subject_marks_obtained;
            $totalPossible = $this->exam->total_marks; // <--- This line is key
            return ($totalObtained / $totalPossible) * 100;
        }
        return 0.0;
    }

    /**
     * Get the letter grade for this subject's marks.
     * Requires access to GradeConfigure model and subject_percentage.
     * You need to fetch your grading scale here.
     * @return string
     */
    public function getSubjectLetterGradeAttribute()
    {
        $percentage = $this->subject_percentage;

        // Fetch the grading scale. You might want to cache this or pass it from the controller
        // For simplicity, fetching it here, but ideally optimize for performance if called frequently
        $gradingScale = GradeConfigure::where('status', 0) // Assuming 0 is active
                                    ->orderBy('grade_point', 'desc')
                                    ->get();

        foreach ($gradingScale as $gradeConfig) {
            list($min, $max) = explode('-', $gradeConfig->class_interval);
            if ($percentage >= (float)$min && $percentage <= (float)$max) {
                return $gradeConfig->letter_grade;
            }
        }
        return 'F'; // Default to F if no grade matches or if percentage is very low
    }

    /**
     * Get the grade point for this subject's marks.
     * Requires access to GradeConfigure model and subject_percentage.
     * @return float
     */
    public function getSubjectGradePointAttribute()
    {
        $percentage = $this->subject_percentage;

        $gradingScale = GradeConfigure::where('status', 0) // Assuming 0 is active
                                    ->orderBy('grade_point', 'desc')
                                    ->get();

        foreach ($gradingScale as $gradeConfig) {
            list($min, $max) = explode('-', $gradeConfig->class_interval);
            if ($percentage >= (float)$min && $percentage <= (float)$max) {
                return (float)$gradeConfig->grade_point;
            }
        }
        return 0.0; // Default to 0.0 if no grade matches or if percentage is very low
    }

    /**
     * Get the pass/fail status for this subject.
     * Requires the related Exam model to have 'passing_marks'.
     * @return string
    */
    public function getSubjectPassStatusAttribute()
    {
        if ($this->relationLoaded('exam') && $this->exam && $this->exam->passing_marks !== null) {
            // This line checks against the exam's passing_marks
            return ($this->total_subject_marks_obtained >= $this->exam->passing_marks) ? 'Pass' : 'Fail';
        }
        return 'N/A';
    }
}