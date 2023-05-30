<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStudent extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "institution_students";

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function studentStatus()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }


    public function educationGrade()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }


    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }


    public function institutionClassStudents()
    {
        return $this->hasMany(InstitutionClassStudents::class, 'institution_id', 'id');
    }
}
