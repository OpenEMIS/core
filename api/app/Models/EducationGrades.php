<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationGrades extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "education_grades";


    public function subjects()
    {
        return $this->belongsToMany(EducationSubjects::class, 'education_grades_subjects', 'education_grade_id', 'education_subject_id')->withPivot('hours_required', 'auto_allocation');
    }


    public function educationProgramme()
    {
        return $this->belongsTo(EducationProgramme::class, 'education_programme_id', 'id');
    }
}
