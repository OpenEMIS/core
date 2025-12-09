<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class StudentCustomFormsFields extends Model
{
    use HasFactory;
use UuidId;

    protected $table = 'student_custom_forms_fields';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_custom_form_id', 'student_custom_field_id', 'section', 'name', 'is_mandatory', 'is_unique', 'order'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    public $incrementing = false;

    public $casts = [
        'id' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
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
