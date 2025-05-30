<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyTemplates extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'description', 'academic_period_id', 'education_grade_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'education_grade_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "competency_templates";








    public function competencyCriterias()
    {
        return $this->hasMany(CompetencyCriterias::class, 'competency_template_id', 'id');
    }

    public function competencyCriteria()
    {
        return $this->hasOne(CompetencyCriterias::class, 'competency_template_id', 'id');
    }
}
