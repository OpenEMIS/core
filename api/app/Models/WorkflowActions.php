<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowActions extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "workflow_actions";
}
