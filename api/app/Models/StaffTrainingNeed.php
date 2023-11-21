<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTrainingNeed extends Model
{
    use HasFactory;


    public $timestamps = false;
    protected $table = "staff_training_needs";

    
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


    public function trainingCourse()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id', 'id');
    }


    public function trainingNeedCategory()
    {
        return $this->belongsTo(TrainingNeedCategory::class, 'training_need_category_id', 'id');
    }
}
