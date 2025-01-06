<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyStatusPeriods extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "survey_status_periods";
    protected $primaryKey = 'id';
    public $incrementing = false;
}
