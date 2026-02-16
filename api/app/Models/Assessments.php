<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessments extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'description', 'excel_template_name', 'excel_template', 'type', 'academic_period_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'education_grade_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;
    protected $table = "assessments";








private function emptyFunction() { return; }
}
