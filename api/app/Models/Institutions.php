<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institutions extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institutions";

    public function areaAdministratives()
    {
        return $this->belongsTo(AreaAdministratives::class, 'area_administrative_id', 'id');
    }


    public function areaEducation()
    {
        return $this->belongsTo(Areas::class, 'area_id', 'id');
    }


    public function educationGrades()
    {
        return $this->belongsToMany(EducationGrades::class, 'institution_grades', 'institution_id', 'education_grade_id');
    }
}
