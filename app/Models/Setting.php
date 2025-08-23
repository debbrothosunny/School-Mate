<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
     protected $fillable = [
        'school_name',
        'address',
        'phone_number',
        'email',
        'principal_name',
        'principal_signature',
        'school_logo',
        'current_session',
    ];
}
