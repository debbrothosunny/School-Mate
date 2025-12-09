<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\GradeConfigure; // Ensure this is imported for grading logic

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
        // Component Marks
        'subjective_marks',
        'objective_marks',
        'practical_marks',
        // Calculated/Status (Cached DB Fields)
        'total_marks_obtained',
        'subject_percentage',
        'subject_letter_grade',
        'subject_grade_point',
        'subject_pass_status',
        'is_absent',
    ];

    /**
     * Cast numeric marks and cached results to appropriate types.
     */
    protected $casts = [
        'subjective_marks' => 'integer',
        'objective_marks' => 'integer',
        'practical_marks' => 'integer',
        'total_marks_obtained' => 'integer',
        'subject_percentage' => 'float',      // Cast for cached result
        'subject_grade_point' => 'float',     // Cast for cached result
        'is_absent' => 'boolean',
    ];

    /**
     * The "booting" method of the model.
     * This is where we calculate the result and cache it into the database columns on save.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $mark) {
            // 1. Calculate and set total marks obtained
            $totalObtained = $mark->calculateTotalSubjectMarksObtained();
            $mark->total_marks_obtained = $totalObtained;

            // Load Subject to get configuration data (full marks, passing marks, etc.)
            $subjectConfig = $mark->getExamSubjectConfig();
            
            // Do not proceed with grading/percentage if subject config is missing
            if (!$subjectConfig) {
                $mark->subject_percentage = 0.0;
                $mark->subject_letter_grade = 'N/A';
                $mark->subject_grade_point = 0.0;
                $mark->subject_pass_status = 'N/A';
                return;
            }

            // 2. Calculate and set percentage
            $mark->subject_percentage = $mark->calculateSubjectPercentage($totalObtained, $subjectConfig);
            $percentage = $mark->subject_percentage;
            
            // 3. Calculate and set pass status (must run before grade/GPA)
            $mark->subject_pass_status = $mark->calculateSubjectPassStatus($totalObtained, $subjectConfig);

            // 4. Calculate and set Letter Grade and Grade Point
            $gradingResult = $mark->calculateGradeAndGPA($percentage, $mark->subject_pass_status);
            $mark->subject_letter_grade = $gradingResult['letter_grade'];
            $mark->subject_grade_point = $gradingResult['grade_point'];

            // Note: The original Accessors for grade, GPA, percentage, and pass status were removed
            // so that Laravel reads the newly cached column values.
        });
    }

    // --- Relationships ---

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassName::class, 'class_id'); 
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'session_id'); 
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    // --- Helper to get Exam/Subject Configuration ---

    /**
     * Gets the full marks and passing marks configuration from the related Subject.
     * @return Subject|null The related Subject model instance.
     */
    protected function getExamSubjectConfig(): ?Subject
    {
        // Load the subject relationship if it hasn't been already
        if (!$this->relationLoaded('subject') && $this->subject_id) {
            $this->load('subject');
        }
        return $this->subject;
    }


    // --- Internal Calculation Methods (Used in the saving event) ---

    /**
     * Calculate the total marks obtained for this subject by summing all components.
     * @return float
     */
    protected function calculateTotalSubjectMarksObtained(): float
    {
        // Ensure that nullable fields default to 0 for calculation
        $subjective = $this->subjective_marks ?? 0;
        $objective = $this->objective_marks ?? 0;
        $practical = $this->practical_marks ?? 0;

        return (float)($subjective + $objective + $practical);
    }

    /**
     * Calculate the percentage obtained for this subject.
     * @param float $totalObtained
     * @param Subject $subjectConfig
     * @return float
     */
    protected function calculateSubjectPercentage(float $totalObtained, Subject $subjectConfig): float
    {
        if ($subjectConfig->full_marks > 0) {
            $totalPossible = $subjectConfig->full_marks;
            return round(($totalObtained / $totalPossible) * 100, 2);
        }
        return 0.0;
    }

    /**
     * Calculate the Grade and GPA based on the subject percentage and pass status.
     * @param float $percentage
     * @param string $passStatus
     * @return array
     */
    protected function calculateGradeAndGPA(float $percentage, string $passStatus): array
    {
        // If failed or absent, immediately return F and 0.0
        if ($passStatus === 'Fail' || $passStatus === 'Absent') {
            return [
                'letter_grade' => ($passStatus === 'Absent' ? 'Absent' : 'F'),
                'grade_point' => 0.0
            ];
        }

        if (!class_exists(GradeConfigure::class)) {
            return ['letter_grade' => 'N/A', 'grade_point' => 0.0];
        }

        // Fetch grading scale (optimally this should be cached via a service provider/repository)
        $gradingScale = GradeConfigure::where('status', 0)
            ->orderBy('grade_point', 'desc')
            ->get();

        foreach ($gradingScale as $gradeConfig) {
             // Assuming class_interval is a string like "80-100"
            if (str_contains($gradeConfig->class_interval, '-')) {
                list($min, $max) = explode('-', $gradeConfig->class_interval);
                if ($percentage >= (float)$min && $percentage <= (float)$max) {
                    return [
                        'letter_grade' => $gradeConfig->letter_grade,
                        'grade_point' => (float)$gradeConfig->grade_point,
                    ];
                }
            }
        }
        return ['letter_grade' => 'F', 'grade_point' => 0.0]; 
    }

    /**
     * Calculate the pass/fail status for this subject based on components and overall total.
     * @param float $totalObtained
     * @param Subject $subjectConfig
     * @return string
     */
    protected function calculateSubjectPassStatus(float $totalObtained, Subject $subjectConfig): string
    {
        if ($this->is_absent) {
            return 'Absent';
        }

        // If overall passing marks aren't set, we can't determine pass/fail.
        if ($subjectConfig->passing_marks === null) {
            return 'N/A';
        }

        // --- 1. Component-wise Pass/Fail Check ---
        // Checks if the marks obtained in any component (subjective, objective, practical) 
        // fall below the required passing marks for that component.
        
        if (($this->subjective_marks ?? 0) < (float)($subjectConfig->subjective_passing_marks ?? 0)) {
            return 'Fail';
        }
        if (($this->objective_marks ?? 0) < (float)($subjectConfig->objective_passing_marks ?? 0)) {
            return 'Fail';
        }
        if (($this->practical_marks ?? 0) < (float)($subjectConfig->practical_passing_marks ?? 0)) {
            return 'Fail';
        }
        
        // --- 2. Overall Total Pass/Fail Check ---
        // If all components passed, check against the overall passing marks.
        return ($totalObtained >= (float)$subjectConfig->passing_marks) ? 'Pass' : 'Fail';
    }
}
