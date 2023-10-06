<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentStatuses extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_statuses";
}
