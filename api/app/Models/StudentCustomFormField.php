<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCustomFormField extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_custom_forms_fields";


    public function studentCustomField()
    {
        return $this->belongsTo(StudentCustomField::class, 'student_custom_field_id', 'id');
    }
}
