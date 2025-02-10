<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestionChoices extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "survey_question_choices";
}
