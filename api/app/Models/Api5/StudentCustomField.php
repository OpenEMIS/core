<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCustomField extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_custom_fields";


    public function studentCustomFieldOption()
    {
        return $this->hasMany(StudentCustomFieldOption::class, 'student_custom_field_id', 'id');
    }
}
