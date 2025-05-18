<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationProgramme extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "education_programmes";


    public function grades()
    {
        return $this->hasMany(EducationGrades::class, 'education_programme_id', 'id');
    }


    public function educationCycle()
    {
        return $this->belongsTo(EducationCycle::class, 'education_cycle_id', 'id');
    }
}
