<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyFormQuestions extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "survey_forms_questions";
}
