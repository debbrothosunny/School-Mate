<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowBook extends Model
{
    use HasFactory;

     protected $fillable = [
        'book_id',
        'student_name',
        'admission_number',
        'class_name',
        'quantity',
        'borrow_date',
        'due_date',
        'return_date',
        'status',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'return_date' => 'date',
    ];
  
    /**
     * Get the book that was borrowed.
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the student who borrowed the book.
    */
    // public function student(): BelongsTo
    // {
    //     return $this->belongsTo(Student::class);
    // }
}
