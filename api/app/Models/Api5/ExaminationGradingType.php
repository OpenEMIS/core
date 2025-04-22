<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExaminationGradingType extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = "examination_grading_types";

    public function gradingOptions()
    {
        return $this->hasMany(ExaminationGradingOption::class,'examination_grading_type_id', 'id');
    }
}
