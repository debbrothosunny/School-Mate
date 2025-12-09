<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'contact_info',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function classes()
    {
        return $this->hasMany(ClassName::class, 'teacher_id');
    }

    // Define relationship: A User can be a Teacher
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

     /**
     * Get the student profile associated with the user.
     * This assumes a one-to-one relationship where a user can have one student record.
     * This is crucial for User::doesntHave('student') to work.
     */
     public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }



    /**
     * Define the relationship to all salary structures (optional, but good practice).
     */
    public function salaryStructures(): MorphMany
    {
        return $this->morphMany(SalaryStructure::class, 'salariable');
    }

    /**
     * Get the *current* effective salary structure (Crucial for the Controller).
     */
    public function currentSalaryStructure(): MorphOne
    {
        // Must match the name used in your Teacher model and Controller
        // Uses 'salariable' as defined in the salary_structures migration
        return $this->morphOne(SalaryStructure::class, 'salariable')
                    ->latest('effective_date'); 
    }
    
    /**
     * The payroll records for this staff member.
     */
    public function payrollRecords(): MorphMany
    {
        // Uses 'staff' as defined in the payroll_records migration and model
        return $this->morphMany(PayrollRecord::class, 'staff', 'staff_type', 'staff_id');
    }
}
