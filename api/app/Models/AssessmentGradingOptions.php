<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentGradingOptions extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'description', 'min', 'max', 'point', 'order', 'visible', 'assessment_grading_type_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'assessment_grading_type_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;
    protected $table = "assessment_grading_options";








private function emptyFunction() { return; }
}
