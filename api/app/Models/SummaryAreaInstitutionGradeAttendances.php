<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryAreaInstitutionGradeAttendances extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'summary_area_institution_grade_attendances';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'area_id', 'area_code', 'area_name', 'institution_id', 'institution_code', 'institution_name', 'education_grade_id', 'education_grade_code', 'education_grade_name', 'attendance_date', 'marked_classes', 'total_classes', 'female_count', 'male_count', 'total_count', 'present_female_count', 'present_male_count', 'present_total_count', 'absent_female_count', 'absent_male_count', 'absent_total_count', 'late_female_count', 'late_male_count', 'late_total_count', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

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
