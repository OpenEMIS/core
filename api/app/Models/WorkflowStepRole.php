<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowStepRole extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "workflow_steps_roles";
}
