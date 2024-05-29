<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendanceMarkType extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_attendance_mark_types";
}
