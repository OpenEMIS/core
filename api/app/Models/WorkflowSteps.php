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
}
