<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessons extends Model
{
    use HasFactory;

    public $timestamps = false;

    use \Awobaz\Compoships\Compoships;

    public function timetables()
    {
        return $this->belongsTo(InstitutionScheduleTimetables::class, 'institution_schedule_timetable_id', 'id');
    }

    public function timeslots()
    {
        return $this->belongsTo(InstitutionScheduleTimeslots::class, 'institution_schedule_timeslot_id', 'id');
    }

    public function scheduleLessonDetails()
    {
        return $this->hasMany(InstitutionScheduleLessonDetails::class, ['day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id'], ['day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id']);
    }
}