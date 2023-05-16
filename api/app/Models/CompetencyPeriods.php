<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyPeriods extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "competency_periods";
}
