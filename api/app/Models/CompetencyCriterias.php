<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyCriterias extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'academic_period_id', 'competency_item_id', 'competency_template_id', 'competency_grading_type_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'competency_item_id', 'competency_template_id', 'competency_grading_type_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

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
        return $this->belongsTo(CompetencyGradingTypes::class, 'competency_grading_type_id', 'id');
    }
}
