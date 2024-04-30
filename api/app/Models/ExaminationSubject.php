<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationSubject extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function gradingType()
    {
        return $this->belongsTo(ExaminationGradingType::class,'examination_grading_type_id', 'id');
    }
}