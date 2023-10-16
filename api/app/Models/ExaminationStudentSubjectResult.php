<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationStudentSubjectResult extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "examination_student_subject_results";

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function examinationCentre()
    {
        return $this->belongsTo(ExaminationCentre::class);
    }

    public function examinationSubject()
    {
        return $this->belongsTo(ExaminationCentre::class);
    }

    public function educationSubject()
    {
        return $this->belongsTo(EducationSubjects::class);
    }
}