<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{

    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'notice_date',
        'status',
        'target_user', // This will be cast to an array
        'created_by',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'notice_date' => 'date',
        'status' => 'integer',
        'target_user' => 'array', // Cast the JSON column to a PHP array
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function className()
    {
        return $this->belongsTo(ClassName::class, 'class_id'); // Ensure foreign key
    }


    /**
     * Scope a query to only include published notices for a specific role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role  The role to filter by (e.g., 'student', 'teacher', 'parent', 'all')
     * @return \Illuminate\Database\Eloquent\Builder
    */
    public function scopeForRole($query, $role)
    {
        return $query->where('status', 0) // Only active notices
            ->where(function ($q) use ($role) {
                $q->whereJsonContains('target_user', $role)
                ->orWhereJsonContains('target_user', 'all');
            });
    }

    /**
     * Scope a query to include only active notices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 0);
    }
}
