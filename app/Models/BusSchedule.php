<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'bus_number',
        'route_name',
        'departure_time',
        'arrival_time',
        'driver_name',
        'capacity',
        'status',
        'class_id',
    ];

    // Define relationship with ClassName model
    public function className()
    {
        // The second argument specifies the foreign key column.
        return $this->belongsTo(ClassName::class, 'class_id');
    }
}
