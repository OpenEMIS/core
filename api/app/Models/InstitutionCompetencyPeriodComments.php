<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionCompetencyPeriodComments extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'comments', 'student_id', 'competency_template_id', 'competency_period_id', 'institution_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'competency_template_id', 'competency_period_id', 'institution_id', 'academic_period_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "institution_competency_period_comments";








private function emptyFunction() { return; }
}
