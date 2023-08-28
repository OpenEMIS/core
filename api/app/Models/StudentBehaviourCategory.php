<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentBehaviourCategory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_behaviour_categories";
}
