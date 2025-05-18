<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyItems extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'academic_period_id', 'competency_template_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'competency_template_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "competency_items";








    public function competencyPeriods()
    {
        return $this->belongsToMany(CompetencyPeriods::class, 'competency_items_periods', 'competency_item_id', 'competency_period_id');
    }
}
