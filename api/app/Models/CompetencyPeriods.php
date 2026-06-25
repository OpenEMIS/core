<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetencyPeriods extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'start_date', 'end_date', 'date_enabled', 'date_disabled', 'academic_period_id', 'competency_template_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'competency_template_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "competency_periods";








private function emptyFunction() { return; }
}
