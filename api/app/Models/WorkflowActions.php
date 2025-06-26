<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowActions extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'description', 'action', 'visible', 'comment_required', 'allow_by_assignee', 'event_key', 'workflow_step_id', 'next_workflow_step_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'workflow_step_id', 'next_workflow_step_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "workflow_actions";








private function emptyFunction() { return; }
}
