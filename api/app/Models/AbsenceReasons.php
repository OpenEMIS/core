<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// POCOR-7394-S 

class AbsenceReasons extends Model
{
    use HasFactory;
    protected $table = "student_absence_reasons";
}
