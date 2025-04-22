<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationCentreExaminationSubject extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $keyType = 'string';
    protected $table = "examination_centres_examinations_subjects";

    public function examinationSubject()
    {
        return $this->belongsTo(ExaminationSubject::class);
    }

    public function educationSubject()
    {
        return $this->belongsTo(EducationSubjects::class);
    }
}
