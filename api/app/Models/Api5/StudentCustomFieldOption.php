<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentCustomFieldOption extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "student_custom_field_options";
}
