<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationGradingOption extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "examination_grading_options";
}
