<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleCurriculumLessons extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code_only', 'institution_schedule_lesson_detail_id', 'institution_subject_id', 'institution_schedule_lesson_detail_id', 'institution_subject_id'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








    public function institution_subject()
    {
        return $this->belongsTo(InstitutionSubjects::class, 'institution_subject_id', 'id');
    }

}
