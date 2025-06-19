<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutionStudentAbsences extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_institution_student_absences';

    // ✅ Allow mass assignment
    protected $fillable = ['institution_id', 'institution_code', 'institution_name', 'area_id', 'area_code', 'area_name', 'area_administrative_id', 'area_administrative_code', 'area_administrative_name', 'student_id', 'openemis_no', 'default_identity_number', 'student_name', 'enrol_start_date', 'enrol_end_date', 'academic_period_id', 'academic_period_code', 'academic_period_name', 'education_grade_id', 'education_grade_code', 'education_grade_name', 'absent_date', 'absent_days', 'absence_subject_period', 'absence_type_id', 'absence_type', 'student_absence_reason_id', 'student_absence_reasons', 'student_status_id', 'student_status', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    public $incrementing = false;
    protected $primaryKey = null;


    // Override getKeyForSaveQuery to handle composite keys








    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    // Override setKeysForSaveQuery to handle composite keys
    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }


}
