<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCustomFieldOption extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_custom_field_options";
}
