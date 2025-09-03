<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassTime extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
    */
    protected $table = 'class_times'; // Ensure this matches your actual table name

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'class_name_id',
        'subject_id',
        'teacher_id',
        'section_id',
        'session_id',
        'day_of_week',
        'room_id',
        'class_time_slot_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
    */
    protected $casts = [
        // 'start_time' and 'end_time' are now on the ClassTimeSlot model, not here.
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the class name associated with the timetable entry.
     */
    public function className(): BelongsTo
    {
        return $this->belongsTo(ClassName::class, 'class_name_id');
    }

    /**
     * Get the subject associated with the timetable entry.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the teacher associated with the timetable entry.
    */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Get the section associated with the timetable entry.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Get the session associated with the timetable entry.
     */
    public function session(): BelongsTo
    {
        // Assuming your session model is named ClassSession and maps to 'class_sessions' table
        return $this->belongsTo(ClassSession::class, 'session_id');
    }

    public function room(): BelongsTo 
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function classTimeSlot(): BelongsTo
    {
        return $this->belongsTo(ClassTimeSlot::class, 'class_time_slot_id');
    }
}
