<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentGradingTypes extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'pass_mark', 'max', 'result_type', 'visible', 'modified_user_id', 'modified', 'created_user_id', 'created', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;
    protected $table = "assessment_grading_types";








    public function assessmentGradingOptions()
    {
        return $this->hasMany(AssessmentGradingOptions::class, 'assessment_grading_type_id', 'id');
    }
}
