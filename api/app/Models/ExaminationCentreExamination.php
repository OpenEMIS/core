<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationCentreExamination extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "examination_centres_examinations";

    public function examination()
    {
        return $this->belongsTo(Examination::class);
    }

    public function examinationCentre()
    {
        return $this->belongsTo(ExaminationCentre::class);
    }
}