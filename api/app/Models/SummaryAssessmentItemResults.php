<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryAssessmentItemResults extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_assessment_item_results';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'assessment_id', 'assessment_code', 'assessment_name', 'assessment_period_id', 'assessment_period_name', 'academic_term', 'subject_id', 'subject_name', 'education_grade_id', 'education_grade', 'institution_id', 'institution_code', 'institution_name', 'institution_provider_id', 'institution_provider', 'area_id', 'area_name', 'institution_class_id', 'institution_class_name', 'count_students', 'count_marked_students', 'missing_marks', 'created'];

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
