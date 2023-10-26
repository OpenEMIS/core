<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examination extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class);
    }

    public function educationGrade()
    {
        return $this->belongsTo(EducationGrades::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(SecurityUsers::class, 'modified_user_id', 'id');
    }
}