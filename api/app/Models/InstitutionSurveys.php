<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionSurveys extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'status_id', 'academic_period_id', 'survey_form_id', 'institution_id', 'assignee_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'status_id', 'academic_period_id', 'survey_form_id', 'institution_id', 'assignee_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_surveys";








    public function surveyForms()
    {
        return $this->belongsTo(SurveyForms::class, 'survey_form_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }
}
