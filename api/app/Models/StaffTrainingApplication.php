<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTrainingApplication extends Model
{
    use HasFactory;


    public $timestamps = false;
    protected $table = "staff_training_applications";

    
    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function staff()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }


    public function session()
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id', 'id');
    }


    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }
}
