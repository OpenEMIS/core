<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryIscedSectors extends Model
{
    use HasFactory;

    protected $table = 'summary_isced_sectors';

    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_sector_id', 'institution_sector_name', 'education_system_id', 'education_system_name', 'education_level_isced_id', 'education_level_isced_name', 'education_level_isced_level', 'total_instiutions', 'total_electricity_institutions', 'total_computer_institutions', 'total_teaching_computer_institutions', 'total_internet_institutions', 'total_toilet_institutions', 'total_improved_toilet_institutions', 'total_single_sex_toilet_institutions', 'total_improved_single_sex_toilet_institutions', 'total_in_use_toilet_institutions', 'total_in_use_improved_toilet_institutions', 'total_in_use_single_sex_toilet_institutions', 'total_improved_in_use_single_sex_toilet_institutions', 'total_drinking_water_institutions', 'total_functional_drinking_water_institutions', 'total_handwashing_facility_institutions', 'total_accessible_room_institutions'];

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
