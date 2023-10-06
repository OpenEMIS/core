<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyItemPeriods extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "competency_items_periods";
}
