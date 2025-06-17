<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowFilters extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "workflows_filters";
    protected $primaryKey = 'id';
    public $incrementing = false;
}
