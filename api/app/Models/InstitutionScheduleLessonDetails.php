<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleLessonDetails extends Model
{
    use HasFactory;

    use \Awobaz\Compoships\Compoships;

    public function institutionScheduleCurriculumLessons()
    {
        return $this->hasOne(InstitutionScheduleCurriculumLessons::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function institutionScheduleNonCurriculumLessons()
    {
        return $this->hasOne(InstitutionScheduleNonCurriculumLessons::class, 'institution_schedule_lesson_detail_id', 'id');
    }

    public function lessonRooms()
    {
        return $this->hasOne(InstitutionScheduleLessonRooms::class, 'institution_schedule_lesson_detail_id', 'id');
    }

}