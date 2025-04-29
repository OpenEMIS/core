<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutionGrades extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'grade_id', 'grade_name', 'total_classes', 'total_classes_female', 'total_classes_male', 'total_classes_mixed', 'total_students', 'total_students_female', 'total_students_male', 'total_home_room_teachers', 'total_secondary_teachers', 'academic_period_id', 'institution_id', 'grade_id'];

    public $timestamps = false;
    protected $table = "summary_institution_grades";


    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    // ✅ Define the primary key
    public $incrementing = false;
    protected $primaryKey = null;








private function emptyFunction() { return; }
}
