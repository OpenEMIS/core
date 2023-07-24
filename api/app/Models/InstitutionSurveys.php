<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionSurveys extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_surveys";

    public function surveyForms()
    {
        return $this->belongsTo(SurveyForms::class, 'survey_form_id', 'id');
    }
}
