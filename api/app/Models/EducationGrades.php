<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationGrades extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'admission_age', 'order', 'visible', 'education_stage_id', 'education_programme_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'education_stage_id', 'education_programme_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "education_grades";








    public function subjects()
    {
        return $this->belongsToMany(EducationSubjects::class, 'education_grades_subjects', 'education_grade_id', 'education_subject_id')->withPivot('hours_required', 'auto_allocation');
    }


    public function educationProgramme()
    {
        return $this->belongsTo(EducationProgramme::class, 'education_programme_id', 'id');
    }
}
