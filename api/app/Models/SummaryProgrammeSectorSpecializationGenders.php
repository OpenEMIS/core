<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryProgrammeSectorSpecializationGenders extends Model
{
    use HasFactory;

    protected $table = 'summary_programme_sector_specialization_genders';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_sector_id', 'institution_sector_name', 'education_system_id', 'education_system_name', 'education_level_isced_id', 'education_level_isced_name', 'education_level_isced_level', 'education_level_id', 'education_level_name', 'education_cycle_id', 'education_cycle_name', 'education_programme_id', 'education_programme_code', 'education_programme_name', 'staff_gender_id', 'staff_gender_name', 'staff_training_category_id', 'staff_training_category_name', 'total_staff_teaching', 'total_staff_teaching_newly_recruited'];

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
