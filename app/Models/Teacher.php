<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'joining_number',
        'image',
        'subject_taught',
        'status',
        'phone_number',
        'qualification',
        'joining_date',
        'designation',

        // New: Class Teacher Assignment (nullable)
        'class_id',
        'section_id',
        'group_id',
    ];

    protected $appends = ['is_class_teacher', 'class_teacher_of'];

    // ========================================
    // Relationships
    // ========================================

    // 1. Teacher belongs to a User (for authentication)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 2. Class Teacher Assignment (One class that this teacher is in-charge of)
    public function assignedClass(): BelongsTo
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function assignedSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function assignedGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id'); // assuming you have a Group model
    }

    // Helper: Check if teacher is a class teacher
    public function isClassTeacher(): bool
    {
        return !is_null($this->class_id);
    }

    // Optional: Get full class teacher info in one go
    public function classTeacherAssignment()
    {
        return $this->assignedClass()
                    ->with(['section', 'group'])
                    ->whereNotNull('class_id');
    }

    // 3. Subjects this teacher teaches in different classes/sections (Many-to-Many)
    public function classNames(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassName::class,
            'class_subjects',
            'teacher_id',
            'class_name_id'
        )->withPivot('subject_id', 'session_id', 'section_id', 'status');
    }

    // 4. Old hasMany (if you still use direct teacher_id in ClassName table - optional)
    public function classes(): HasMany
    {
        return $this->hasMany(ClassName::class, 'teacher_id');
    }

    // 5. Exam Schedules
    public function examSchedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class, 'teacher_id');
    }

    // 6. Salary & Payroll
    public function salaryStructures(): MorphMany
    {
        return $this->morphMany(SalaryStructure::class, 'salariable');
    }

    public function currentSalaryStructure(): MorphOne
    {
        return $this->morphOne(SalaryStructure::class, 'salariable')
                    ->latest('effective_date');
    }

    public function payrollRecords(): MorphMany
    {
        return $this->morphMany(PayrollRecord::class, 'staff', 'staff_type', 'staff_id');
    }

    // 7. Attendance
    public function attendances(): HasMany
    {
        return $this->hasMany(TeacherAttendance::class);
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeClassTeachers($query)
    {
        return $query->whereNotNull('class_id');
    }

    public function scopeRegularTeachers($query)
    {
        return $query->whereNull('class_id');
    }

    // ========================================
    // Accessors (Optional - for blade display)
    // ========================================

    public function getClassTeacherLabelAttribute(): string
    {
        if (!$this->isClassTeacher()) {
            return '<span class="badge badge-secondary">Not Assigned</span>';
        }

        $class = $this->assignedClass?->name ?? '';
        $section = $this->assignedSection?->name ?? '';
        $group = $this->assignedGroup?->name ?? '';

        return "<span class='badge badge-success'>Class Teacher: $class $section $group</span>";
    }

    public function getDesignationBadgeAttribute(): string
    {
        $colors = [
            'Head Teacher'       => 'danger',
            'Senior Teacher'     => 'info',
            'Junior Teacher'     => 'primary',
            'Assistant Teacher'  => 'secondary',
        ];

        $color = $colors[$this->designation] ?? 'dark';

        return "<span class='badge badge-$color'>{$this->designation}</span>";
    }


    /**
     * Check if teacher is assigned as class teacher
    */
    public function getIsClassTeacherAttribute(): bool
    {
        return !is_null($this->class_id);
    }

    /**
     * Beautiful formatted "Class Teacher Of" text
     * Example: "Class Six A Science"
    */
    public function getClassTeacherOfAttribute(): ?string
    {
        if (!$this->isClassTeacher()) {
            return null;
        }

        $parts = [];

        if ($this->assignedClass?->class_name) {
            $parts[] = $this->assignedClass->class_name;
        }

        if ($this->assignedSection?->name) {
            $parts[] = $this->assignedSection->name;
        }

        if ($this->assignedGroup?->name) {
            $parts[] = $this->assignedGroup->name;
        }

        return !empty($parts) ? implode(' ', $parts) : 'Assigned Class';
    }
    
}