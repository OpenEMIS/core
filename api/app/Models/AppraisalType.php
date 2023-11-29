<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalType extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "appraisal_types";
}
