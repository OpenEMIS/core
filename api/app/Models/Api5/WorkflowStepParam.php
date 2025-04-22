<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStepParam extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "workflow_steps_params";
}
