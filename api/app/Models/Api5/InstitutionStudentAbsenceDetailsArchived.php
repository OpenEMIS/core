<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStudentAbsenceDetailsArchived extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_student_absence_details_archived";
}
