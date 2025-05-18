<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionStudentAbsenceDays extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_id', 'institution_id', 'absence_type_id', 'absent_days', 'start_date', 'end_date', 'student_id', 'institution_id', 'absence_type_id'];

    public $timestamps = false;
    protected $table = "institution_student_absence_days";








private function emptyFunction() { return; }
}
