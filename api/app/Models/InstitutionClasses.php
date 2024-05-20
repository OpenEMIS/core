<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClasses extends Model
{
    use HasFactory;

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