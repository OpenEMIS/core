<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyGradingtypes extends Model
{
    use HasFactory;
    
    public $timestamps = false;
    protected $table = "competency_grading_types";
}
