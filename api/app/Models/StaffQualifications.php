<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\NumericId;

class StaffQualifications extends Model
{
    use HasFactory;
    use NumericId;


    protected $table = 'staff_qualifications';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'document_no', 'graduate_year', 'qualification_institution', 'gpa', 'file_name', 'file_content', 'education_field_of_study_id', 'staff_id', 'qualification_title_id', 'qualification_country_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

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
            return (int)$maxId + 1;
        });
    }

    protected static function boot()
    {
        parent::boot();
        self::bootNumericId();
    }

    // Override getKeyForSaveQuery to handle composite keys


    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];

    }

}




