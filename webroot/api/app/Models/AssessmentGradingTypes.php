<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentGradingTypes extends Model
{
    use HasFactory;
    protected $table = "assessment_grading_types";

    public function assessmentGradingOptions()
    {
        return $this->hasMany(AssessmentGradingOptions::class, 'assessment_grading_type_id', 'id');
    }
}
