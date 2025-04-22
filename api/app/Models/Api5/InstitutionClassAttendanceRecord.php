<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClassAttendanceRecord extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_class_attendance_records";
}
