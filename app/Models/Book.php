<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'author',
        'publisher',
        'publication_date',
        'isbn',
        'quantity',
        'available_quantity',
        'genre',
        'cover_image_path',
        'status',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'status' => 'boolean',
    ];

    /**
     * Get the borrow records for the book.
    */
    public function borrowRecords(): HasMany
    {
        return $this->hasMany(BorrowBook::class);
    }
}
