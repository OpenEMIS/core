<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessonDetails extends Model
{
    use HasFactory;

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