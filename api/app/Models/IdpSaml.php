<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdpSaml extends Model
{
    use HasFactory;

    protected $table = 'idp_saml';

    // ✅ Allow mass assignment
    protected $fillable = ['system_authentication_id', 'idp_entity_id', 'idp_sso', 'idp_sso_binding', 'idp_slo', 'idp_slo_binding', 'idp_x509cert', 'idp_cert_fingerprint', 'idp_cert_fingerprint_algorithm', 'sp_entity_id', 'sp_acs', 'sp_slo', 'sp_name_id_format', 'sp_private_key', 'sp_metadata'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    protected $primaryKey = 'system_authentication_id';
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
