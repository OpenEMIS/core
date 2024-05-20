<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStaffAppraisal extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_staff_appraisals";


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


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }


    public function appraisalType()
    {
        return $this->belongsTo(AppraisalType::class, 'appraisal_type_id', 'id');
    }


    public function appraisalForm()
    {
        return $this->belongsTo(AppraisalForm::class, 'appraisal_form_id', 'id');
    }


    public function appraisalPeriod()
    {
        return $this->belongsTo(AppraisalPeriod::class, 'appraisal_period_id', 'id');
    }

}
