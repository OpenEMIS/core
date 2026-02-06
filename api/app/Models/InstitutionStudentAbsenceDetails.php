<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionStudentAbsenceDetails extends Model
{
    use HasFactory;
use InstitutionScope;

    // ✅ Allow mass assignment
    protected $fillable = ['student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'date', 'period', 'comment', 'absence_type_id', 'student_absence_reason_id', 'subject_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'absence_type_id', 'student_absence_reason_id', 'subject_id', 'modified_user_id', 'created_user_id'];

    protected $table = "institution_student_absence_details";
    public $timestamps = false;

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Define the primary key
    protected $dates = ['modified', 'created'];
    protected $primaryKey = ["student_id","institution_id","academic_period_id","institution_class_id","date","period","subject_id"];









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

    public function attendancePeriod()
    {
        return $this->belongsTo(
            StudentAttendancePerDayPeriod::class,
            'period', // FK column
            'id'
        );
    }

    public function period()
    {
        return $this->belongsTo(StudentAttendancePerDayPeriod::class, 'period', 'id');
    }


    public function subject()
    {
        return $this->belongsTo(InstitutionSubjects::class, 'subject_id', 'id');
    }

    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

}
