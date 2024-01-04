<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClassGrades extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_class_grades";

    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }
}