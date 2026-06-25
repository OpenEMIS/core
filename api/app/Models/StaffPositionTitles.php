<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\NumericId;

class StaffPositionTitles extends Model
{
    use HasFactory;
    use NumericId;

    protected $table = 'staff_position_titles';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'type', 'staff_position_categories_id', 'security_role_id', 'staff_leave_policy_id', 'file_name', 'file_content', 'order', 'visible', 'editable', 'default', 'international_code', 'national_code', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    protected $primaryKey = 'id';
    public $incrementing = false;
    public static function getNextId()
    {
        return \DB::transaction(function () {
            $maxId = self::max('id');
            return (int) $maxId + 1;
        });
    }

    protected static function boot(): void
    {
        parent::boot();
        self::bootNumericId();
    }

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
