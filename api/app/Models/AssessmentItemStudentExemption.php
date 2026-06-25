<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentItemStudentExemption extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = "assessment_item_student_exemptions";
    protected $fillable = [
        'assessment_id',
        'education_subject_id',
        'student_id',
        'institution_class_id',
        'education_grade_id',
        'assessment_period_id',
        'modified_user_id',
        'modified',
        'created_user_id',
        'created'
    ];
}
