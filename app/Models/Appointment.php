<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_time', 
        'end_time', 
        'is_recurring',
        'day_of_week',
        'time_of_day',
    ];
    
    public function timeSlot()
    {
        return $this->belongsTo(CrudEvents::class);
    }

}
