<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationSystem extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "education_systems";


    public function levels()
    {
        return $this->hasMany(EducationLevel::class, 'education_system_id', 'id');
    }
}
