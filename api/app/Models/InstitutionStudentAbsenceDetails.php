<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStudentAbsenceDetails extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_student_absence_details";


    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }


    public function educationGrade()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }


    public function institutionClass()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }


    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }


    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function absenceType()
    {
        return $this->belongsTo(AbsenceTypes::class, 'absence_type_id', 'id');
    }


    public function studentAbsenceReason()
    {
        return $this->belongsTo(StudentAbsenceReason::class, 'student_absence_reason_id', 'id');
    }


    public function period()
    {
        return $this->belongsTo(StudentAttendancePerDayPeriod::class, 'period', 'id');
    }


    public function subject()
    {
        return $this->belongsTo(InstitutionSubjects::class, 'subject_id', 'id');
    }

}
