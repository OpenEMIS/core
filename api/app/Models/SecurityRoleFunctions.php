<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityRoleFunctions extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['_view', '_edit', '_add', '_delete', '_execute',
        'security_role_id', 'security_function_id',
        'modified_user_id', 'modified',
        'created_user_id', 'created',
        ];
    protected $dates = ['modified', 'created'];
    protected $table = "security_role_functions";

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Define the primary key
    protected $primaryKey = ["security_role_id","security_function_id"];









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
