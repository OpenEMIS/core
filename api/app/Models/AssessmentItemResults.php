<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class AssessmentItemResults extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'marks', 'assessment_grading_option_id', 'student_id', 'assessment_id', 'education_subject_id', 'education_grade_id', 'academic_period_id', 'assessment_period_id', 'institution_id', 'institution_classes_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'assessment_grading_option_id', 'student_id', 'assessment_id', 'education_subject_id', 'education_grade_id', 'academic_period_id', 'assessment_period_id', 'institution_id', 'institution_classes_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "assessment_item_results";
    protected $primaryKey = 'id';
    public $incrementing = false;








    public function assessmentGradingOption()
    {
        return $this->belongsTo(AssessmentGradingOptions::class, 'assessment_grading_option_id', 'id');
    }

    public function assessmentPeriod()
    {
        return $this->belongsTo(AssessmentPeriod::class, 'assessment_period_id', 'id');
    }
}
