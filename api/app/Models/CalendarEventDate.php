<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEventDate extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "calendar_event_dates";

    public function calendarEvent()
    {
        return $this->belongsTo(CalendarEvent::class, 'calendar_event_id', 'id');
    }
}
