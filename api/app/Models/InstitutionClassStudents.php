<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClassStudents extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_class_students";

    public function institutionClass()
    {
        return $this->belongsTo(InstitutionClasses::class);
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }
}