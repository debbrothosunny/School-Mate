<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSchedule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'exam_schedules';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exam_id',
        'class_id',
        'section_id',
        'session_id',
        'teacher_id',
        'subject_id',
        'exam_slot_id',
        'room_id', 
        'exam_date',
        'day_of_week',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exam_date' => 'date',
        'status' => 'integer',
    ];

    /**
     * Get the Exam that this schedule belongs to.
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the ClassName that this schedule belongs to.
     */
    public function className(): BelongsTo
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    /**
     * Get the Section that this schedule belongs to.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the ClassSession that this schedule belongs to.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * Get the Teacher assigned to this schedule.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the Subject for this schedule.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the Room for this schedule.
    */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the ExamSlot that this schedule belongs to.
    */
    public function examSlot()
    {
        return $this->belongsTo(ExamTimeSlot::class, 'exam_slot_id');
    }

    public function examSeatPlans(): HasMany
    {
        return $this->hasMany(ExamSeatPlan::class);
    }
}
