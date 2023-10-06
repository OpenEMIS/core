<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentPeriod extends Model
{
    use HasFactory;
    protected $table = "assessment_periods";

    public function assessments()
    {
        return $this->hasOne(Assessments::class, 'id', 'assessment_id');
    }
}
