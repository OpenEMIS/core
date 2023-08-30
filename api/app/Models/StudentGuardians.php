<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGuardians extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = "student_guardians";
    protected $primaryKey = 'id';
    public $incrementing = false;
}
