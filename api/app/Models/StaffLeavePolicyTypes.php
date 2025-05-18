<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class StaffLeavePolicyTypes extends Model
{
    use UuidId;
    use HasFactory;

    public $timestamps = false;

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Disable Laravel's default timestamps
    public $casts = [
        'id' => 'string',
    ];

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $table = 'staff_leave_policy_types';

    // ✅ Define the primary key
    protected $fillable = ['id', 'staff_leave_policy_id', 'staff_leave_type_id', 'days', 'rollover'];
    protected $dates = ['modified', 'created'];

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }

    // Override getKeyForSaveQuery to handle composite keys

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }


    // Override setKeysForSaveQuery to handle composite keys








    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }


}
