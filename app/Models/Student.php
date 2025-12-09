<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ClassSession; 

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'class_id',
        'age',
        'date_of_birth',
        'gender',
        'admission_date',
        'parent_name',
        'session_id',
        'group_id',
        'section_id',
        'image',
        'address',
        'contact',
        'status',
        'enrollment_status',
        'user_id',
        'admission_number',
        'roll_number',
        'admission_fee_amount',
        'admission_fee_paid',
        'payment_method',
        'blood_group', 
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
    */
    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
        'status' => 'integer',
    ];



    // Relationship: A Student belongs to a ClassName (e.g., 'Class 1')
    public function className(): BelongsTo
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    // Relationship: A Student belongs to a Session (assuming Session is your model for classssessions)
    public function session(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class, 'session_id'); // Corrected: Changed to Session::class
    }

    // Relationship: A Student belongs to a Group
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    // Relationship: A Student belongs to a Section
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    // Relationship: A Student has many Attendance records
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // Relationship: A Student belongs to a User (for linking to login accounts)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the borrow records for the student.
    */
    
    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowBook::class);
    }

     public function examSeatPlans(): HasMany
    {
        return $this->hasMany(ExamSeatPlan::class);
    }

  

    public function feeAssignments()
    {
        return $this->hasMany(StudentFeeAssignment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }



    /**
     * Get the exam results for the student.
    */
    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class, 'student_id');
    }


    // In Student Model
public function getBase64ImageAttribute()
{
    $imageFile = $this->image;
    $studentImagePath = public_path('uploads/students/' . $imageFile);

    // Check if image is set AND file exists
    if (!empty($imageFile) && file_exists($studentImagePath)) {
        $path = $studentImagePath;
    } else {
        // Fallback: This path must exist or the PDF will break again!
        // For production, you must ensure this fallback image exists.
        $path = public_path('images/placeholder.png'); 
    }
    
    // Safety check for file_get_contents
    if (!file_exists($path)) {
        // If even the fallback is missing, return empty data
        return [
            'src' => '',
            'alt' => 'Image missing on server.',
        ];
    }

    $imageData = base64_encode(file_get_contents($path));
    $imageExtension = pathinfo($path, PATHINFO_EXTENSION);
    $mimeType = ($imageExtension == 'png' ? 'image/png' : 'image/jpeg');

    return [
        'src' => 'data:' . $mimeType . ';base64,' . $imageData,
        'alt' => $this->name . ' Photo',
    ];
}

    
}
