<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionStudentAbsences extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'date', 'absence_type_id', 'institution_student_absence_day_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'institution_id', 'academic_period_id', 'institution_class_id', 'education_grade_id', 'absence_type_id', 'institution_student_absence_day_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_student_absences";








private function emptyFunction() { return; }
}
