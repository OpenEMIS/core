<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentItemResults extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "assessment_item_results";
    protected $primaryKey = 'id';
    public $incrementing = false;


    public function assessmentGradingOption()
    {
        return $this->belongsTo(AssessmentGradingOptions::class, 'assessment_grading_option_id', 'id');
    }
}
