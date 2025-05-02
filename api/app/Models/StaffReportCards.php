<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class StaffReportCards extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'staff_report_cards';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'status', 'file_name', 'file_content', 'file_content_pdf', 'started_on', 'completed_on', 'staff_profile_template_id', 'staff_id', 'institution_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['staff_profile_template_id', 'staff_id', 'institution_id', 'academic_period_id'];
    public $incrementing = false;

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
