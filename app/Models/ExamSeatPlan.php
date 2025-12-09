<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamSeatPlan extends Model
{
    protected $fillable = [
        'exam_id',
        'class_id',
        'section_id',
        'session_id',
        'room_id',
        'group_id', // Added group_id
        'student_id',
        'seat_number',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function session()
    {
        return $this->belongsTo(ClassSession::class, 'session_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}