<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'notice_title',
        'content',
        'start_date',
        'end_date',
        'status',
        'target_user',
        'created_by',
   
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'target_user' => 'array',
        'status' => 'integer',
    ];

    /**
     * Get the user who created the notice.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the class this notice belongs to (if applicable).
     */
    public function className()
    {
        return $this->belongsTo(ClassName::class, 'class_id');
    }

    /**
     * Scope a query to only include published notices for a specific role.
     *
     * We'll assume '1' is the published status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role  The role to filter by (e.g., 'student', 'teacher', 'parent', 'all')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForRole($query, $role)
    {
        return $query->where('status', 1) // 1 for published notices
            ->where(function ($q) use ($role) {
                $q->whereJsonContains('target_user', $role)
                  ->orWhereJsonContains('target_user', 'all');
            });
    }

    /**
     * Scope a query to include only active (published) notices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 0)
                     ->where('start_date', '<=', now())
                     ->where('end_date', '>=', now());
    }
}
