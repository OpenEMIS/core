<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyTableRows extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "survey_table_rows";
}
