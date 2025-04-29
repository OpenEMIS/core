<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowSteps extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'category', 'is_editable', 'is_removable', 'is_system_defined', 'workflow_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'workflow_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

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

    public function WorkflowActions()
    {
        return $this->hasMany(WorkflowActions::class, 'workflow_step_id', 'id');
    }
}
