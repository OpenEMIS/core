<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStaffReleases extends Model
{
    use HasFactory;

    protected $table = 'institution_staff_releases';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'previous_FTE', 'previous_start_date', 'previous_end_date', 'new_FTE', 'new_start_date', 'new_end_date', 'comment', 'all_visible', 'modified_user_id', 'modified', 'created_user_id', 'created', 'status_id', 'assignee_id', 'staff_id', 'previous_institution_id', 'previous_institution_staff_id', 'previous_staff_type_id', 'new_institution_id', 'new_institution_position_id', 'new_staff_type_id'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key


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
