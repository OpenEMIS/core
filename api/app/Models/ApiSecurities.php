<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSecurities extends Model
{
    use HasFactory;

    protected $table = 'api_securities';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'model', 'index', 'view', 'add', 'edit', 'delete', 'execute'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    public $incrementing = false;

    public $casts = [
        'id' => 'integer',
        'index' => 'integer',
        'view' => 'integer',
        'add' => 'integer',
        'edit' => 'integer',
        'delete' => 'integer',
        'execute' => 'integer'
    ];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    public static function getNextId()
    {
        return \DB::transaction(function () {
            $maxId = self::max('id');
            return (int) $maxId + 1;
        });
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
