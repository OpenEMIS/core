<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "calendar_events";


    public function calendarType()
    {
        return $this->belongsTo(CalendarType::class, 'calendar_type_id', 'id');
    }
}
