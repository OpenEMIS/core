<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionSubjects extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_subjects";

    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }

    public function educationSubjects()
    {
        return $this->belongsTo(EducationSubjects::class, 'education_subject_id', 'id');
    }

    public function classes()
    {
        return $this->hasMany(InstitutionClassSubjects::class, 'institution_subject_id', 'id');
    }

    public function rooms()
    {
        return $this->hasMany(InstitutionSubjectRooms::class, 'institution_subject_id', 'id');
    }


    public function staff()
    {
        return $this->hasMany(InstitutionSubjectStaff::class, 'institution_subject_id', 'id');
    }


    public function students()
    {
        return $this->hasMany(InstitutionSubjectStudents::class, 'institution_subject_id', 'id');
    }
}
