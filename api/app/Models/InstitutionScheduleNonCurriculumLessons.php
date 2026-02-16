<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleNonCurriculumLessons extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'institution_schedule_lesson_detail_id', 'institution_schedule_lesson_detail_id'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








private function emptyFunction() { return; }
}
