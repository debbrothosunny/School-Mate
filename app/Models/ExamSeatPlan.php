<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSeatPlan extends Model
{
    use HasFactory;

    // By default, Laravel assumes 'exam_seat_plans' table.
    // If your table name differs significantly, you might set: protected $table = 'your_table_name';

    protected $fillable = [
        'exam_schedule_id',
        'student_id',
        'seat_number',
    ];

    /**
     * Get the exam schedule that owns the seat plan.
     */
    public function examSchedule(): BelongsTo
    {
        return $this->belongsTo(ExamSchedule::class);
    }

    /**
     * Get the student associated with the seat plan.
    */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}