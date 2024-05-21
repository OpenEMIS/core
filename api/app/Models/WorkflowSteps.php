<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowSteps extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "workflow_steps";

    public function workflows()
    {
        return $this->belongsTo(Workflows::class, 'workflow_id', 'id');
    }


    public function workflowStepRole()
    {
        return $this->belongsTo(WorkflowStepRole::class, 'id', 'workflow_step_id');
    }


    public function workflowStepParam()
    {
        return $this->belongsTo(WorkflowStepParam::class, 'id', 'workflow_step_id');
    }
}
