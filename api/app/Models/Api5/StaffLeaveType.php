<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLeaveType extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "staff_leave_types";
}
