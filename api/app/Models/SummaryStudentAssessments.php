<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryStudentAssessments extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_student_assessments';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_code', 'academic_period_name', 'area_id', 'area_code', 'area_name', 'institution_id', 'institution_code', 'institution_name', 'grade_id', 'grade_code', 'grade_name', 'institution_classes_id', 'institution_classes_name', 'homeroom_teacher_id', 'homeroom_teacher_name', 'subject_id', 'subject_code', 'subject_name', 'subject_weight', 'assessment_id', 'assessment_code', 'assessment_name', 'period_id', 'period_code', 'period_name', 'academic_term', 'period_weight', 'student_id', 'student_name', 'latest_mark', 'total_mark', 'average_mark', 'institution_average_mark', 'area_average_mark', 'created'];

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
