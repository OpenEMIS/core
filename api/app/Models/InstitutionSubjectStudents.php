<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionSubjectStudents extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_subject_students";


    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }

    public function studentStatus()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }

    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }

    public function class()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }
}