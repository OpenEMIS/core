<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionVisitRequest extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_visit_requests";

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }
    
    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }


    public function qualityVisitType()
    {
        return $this->belongsTo(QualityVisitType::class, 'quality_visit_type_id', 'id');
    }


    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }
}
