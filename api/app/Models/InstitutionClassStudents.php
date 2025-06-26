<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionClassStudents extends Model
{
    use HasFactory;
    use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'next_institution_class_id', 'institution_id', 'student_status_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'next_institution_class_id', 'institution_id', 'student_status_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    protected $primaryKey = 'id';
    public $incrementing = false;
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


    public function institutionClass()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }


    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }

    public function educationGrade()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }


    //For POCOR-8363 Start...
    public function createdUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function modifiedUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'modified_user_id', 'id');
    }
    //For POCOR-8363 End...

}
