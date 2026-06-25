<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SecurityGroupInstitutions extends Model
{
    use HasFactory;
use InstitutionScope;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Disable Laravel's default timestamps
    public $incrementing = false;
    protected $fillable = ['security_group_id', 'institution_id', 'created_user_id', 'created', 'security_group_id', 'institution_id', 'created_user_id'];
    protected $table = 'security_group_institutions';
    protected $primaryKey = ["security_group_id", "institution_id"];
    protected $dates = ['modified', 'created'];









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

    // Override setKeysForSaveQuery to handle composite keys
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
