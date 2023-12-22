<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClassStudents extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_class_students";


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }


    public function studentStatus()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }
}
