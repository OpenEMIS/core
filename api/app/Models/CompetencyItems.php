<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyItems extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "competency_items";


    public function competencyPeriods()
    {
        return $this->belongsToMany(CompetencyPeriods::class, 'competency_items_periods', 'competency_item_id', 'competency_period_id');
    }
}
