<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "training_sessions";

    protected $appends = ['code_name'];


    public function getCodeNameAttribute()
    {
        return $this->attributes['code']. ' - ' .$this->attributes['name'];
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


    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'training_course_id', 'id');
    }
}
