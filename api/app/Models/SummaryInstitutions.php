<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutions extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'total_grades', 'total_classes', 'total_lands', 'total_land_size', 'total_buildings', 'total_building_sizes', 'total_floors', 'total_floor_sizes', 'total_rooms', 'total_room_sizes', 'total_room_classrooms', 'total_room_classroom_sizes', 'total_students', 'total_students_female', 'total_students_male', 'total_staff_teaching', 'total_staff_teaching_female', 'total_staff_teaching_male', 'total_staff_non_teaching', 'total_staff_non_teaching_female', 'total_staff_non_teaching_male', 'academic_period_id', 'institution_id'];

    public $timestamps = false;
    protected $table = "summary_institutions";


    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    // ✅ Define the primary key
    public $incrementing = false;
    protected $primaryKey = null;








private function emptyFunction() { return; }
}
