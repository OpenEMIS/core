<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityGroupAreas extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = ['security_group_id', 'area_id',
        'created_user_id', 'created',
    ];
    protected $table = "security_group_areas";
    protected $primaryKey = ["security_group_id", "area_id"];









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
