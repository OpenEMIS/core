<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationCenterExaminationSubjectStudent extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "examination_centres_examinations_subjects_students";

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
        return $this->belongsTo(ExaminationSubject::class);
    }

    public function educationSubject()
    {
        return $this->belongsTo(EducationSubjects::class);
    }
}