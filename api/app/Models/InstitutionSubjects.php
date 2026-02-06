<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionSubjects extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'no_of_seats', 'total_male_students', 'total_female_students', 'institution_id', 'education_grade_id', 'education_subject_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'institution_id', 'education_grade_id', 'education_subject_id', 'academic_period_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_subjects";








    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }

    public function educationSubjects()
    {
        return $this->belongsTo(EducationSubjects::class, 'education_subject_id', 'id');
    }

    public function classes()
    {
        return $this->hasMany(InstitutionClassSubjects::class, 'institution_subject_id', 'id');
    }

    public function rooms()
    {
        return $this->hasMany(InstitutionSubjectRooms::class, 'institution_subject_id', 'id');
    }


    public function staff()
    {
        return $this->hasMany(InstitutionSubjectStaff::class, 'institution_subject_id', 'id');
    }


    public function students()
    {
        return $this->hasMany(InstitutionSubjectStudents::class, 'institution_subject_id', 'id');
    }


    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }
}
