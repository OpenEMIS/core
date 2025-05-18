<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessonDetails extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'lesson_type', 'day_of_week', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'institution_schedule_timeslot_id', 'institution_schedule_timetable_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    use \Awobaz\Compoships\Compoships;








    public function schedule_curriculum_lesson()
    {
        return $this->hasOne(InstitutionScheduleCurriculumLessons::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function schedule_non_curriculum_lesson()

    {
        return $this->hasOne(InstitutionScheduleNonCurriculumLessons::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function schedule_lesson_room()
    {
        return $this->hasOne(InstitutionScheduleLessonRooms::class, 'institution_schedule_lesson_detail_id', 'id');
    }

}
