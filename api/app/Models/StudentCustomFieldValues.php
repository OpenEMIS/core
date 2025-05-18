<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class StudentCustomFieldValues extends Model
{
    use HasFactory;
    use UuidId;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    public $incrementing = false;
    protected $fillable = ['id', 'text_value', 'number_value', 'decimal_value', 'textarea_value', 'date_value', 'time_value', 'file', 'student_custom_field_id', 'student_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_custom_field_id', 'student_id', 'modified_user_id', 'created_user_id'];
    protected $dates = ['modified', 'created'];
    protected $table = "student_custom_field_values";
    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }

    //For POCOR-8491 Start...








    public function studentCustomField()
    {
        return $this->belongsTo(StudentCustomField::class, 'student_custom_field_id', 'id');
    }
    //For POCOR-8491 End...
}
