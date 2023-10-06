<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyCriterias extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "competency_criterias";


    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }


    public function competencyItem()
    {
        return $this->belongsTo(CompetencyItems::class, 'competency_item_id', 'id');
    }


    public function competencyTemplate()
    {
        return $this->belongsTo(CompetencyTemplates::class, 'competency_template_id', 'id');
    }


    public function competencyGradingtype()
    {
        return $this->belongsTo(CompetencyGradingtypes::class, 'competency_grading_type_id', 'id');
    }
}
