<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationLevel extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "education_levels";

    public function cycles()
    {
        return $this->hasMany(EducationCycle::class, 'education_level_id', 'id');
    }
}
