<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationGradesSubject extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "education_grades_subjects";
}