<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCustomFieldValues extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_custom_field_values";
    protected $primaryKey = 'id';
    public $incrementing = false;


    //For POCOR-8491 Start...
    public function studentCustomField()
    {
        return $this->belongsTo(StudentCustomField::class, 'student_custom_field_id', 'id');
    }
    //For POCOR-8491 End...
}
