<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessons extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $keyType = 'string';

    protected $casts = [
        'id' => 'string',
    ];

    public $incrementing = false;

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
