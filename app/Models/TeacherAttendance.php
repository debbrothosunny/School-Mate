<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'teacher_attendances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'date',
        'status',
        'in_time',
        'out_time',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        // Note: 'time' columns in MySQL don't need casting to Carbon objects
        // unless you specifically need time manipulation logic.
    ];

    // --- Relationships ---

    /**
     * Get the teacher that owns the attendance record.
     */
    public function teacher(): BelongsTo
    {
        // This links the attendance record back to the Teacher model using the 'teacher_id' foreign key.
        return $this->belongsTo(Teacher::class);
    }
}