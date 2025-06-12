<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyResponse extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "survey_responses";
    protected $primaryKey = 'id';
    public $incrementing = false;
}
