<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutionStudentSubjectResults extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_institution_student_subject_results';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'area_id', 'area_code', 'area_name', 'area_administrative_id', 'area_administrative_code', 'area_administrative_name', 'institution_provider_id', 'institution_provider_name', 'institution_ownership_id', 'institution_ownership_name', 'institution_gender_id', 'institution_gender_name', 'institution_id', 'institution_code', 'institution_name', 'education_grade_id', 'education_grade_code', 'education_grade_name', 'student_id', 'student_openemis_no', 'student_first_name', 'student_middle_name', 'student_third_name', 'student_last_name', 'student_gender_id', 'student_gender_name', 'student_default_identity_id', 'student_default_identity_type', 'student_default_identity_number', 'student_default_nationality_id', 'student_default_nationality_name', 'education_subject_id', 'education_subject_code', 'education_subject_name', 'total_avg_results', 'male_avg_results', 'female_avg_results', 'created'];

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
