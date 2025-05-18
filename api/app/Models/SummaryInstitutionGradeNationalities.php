<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutionGradeNationalities extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'grade_id', 'grade_name', 'nationality_id', 'nationality_name', 'total_students', 'total_students_female', 'total_students_male', 'academic_period_id', 'institution_id', 'grade_id', 'nationality_id'];

    public $timestamps = false;
    protected $table = "summary_institution_grade_nationalities";

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    // ✅ Define the primary key
    public $incrementing = false;
    protected $primaryKey = null;








private function emptyFunction() { return; }
}
