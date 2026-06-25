<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionClasses extends Model
{
    use HasFactory;
    use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'class_number', 'capacity', 'total_male_students', 'total_female_students', 'staff_id', 'institution_shift_id', 'institution_id', 'institution_unit_id', 'institution_course_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'staff_id', 'institution_shift_id', 'institution_id', 'institution_unit_id', 'institution_course_id', 'academic_period_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_classes";








    public function grades()
    {
        return $this->hasMany(InstitutionClassGrades::class, 'institution_class_id', 'id');
    }


    public function subjects()
    {
        return $this->hasMany(InstitutionClassSubjects::class, 'institution_class_id', 'id');
    }


    public function students()
    {
        return $this->hasMany(InstitutionClassStudents::class, 'institution_class_id', 'id');
    }

    public function secondary_teachers()
    {
        return $this->hasMany(InstitutionClassSecondaryStaff::class, 'institution_class_id', 'id');
    }

    public function studentSubjects()
    {
        return $this->hasMany(InstitutionSubjectStudents::class, 'institution_class_id', 'id');
    }
}
