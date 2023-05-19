<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyTemplates extends Model
{
    use HasFactory;

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
