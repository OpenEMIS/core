<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationGrades extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "education_grades";
}
