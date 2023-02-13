<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducationCycle extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "education_cycles";

    public function programmes()
    {
        return $this->hasMany(EducationProgramme::class, 'education_cycle_id', 'id');
    }
}
