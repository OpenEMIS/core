<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipsScholarshipAttachmentTypes extends Model
{
    use HasFactory;

    public $timestamps = false;

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Disable Laravel's default timestamps
    protected $table = 'scholarships_scholarship_attachment_types';

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['scholarship_id', 'scholarship_attachment_type_id', 'is_mandatory'];

    // ✅ Define the primary key
    protected $dates = ['modified', 'created'];
    protected $primaryKey = ['scholarship_id', 'scholarship_attachment_type_id'];

    // Override getKeyForSaveQuery to handle composite keys

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
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
