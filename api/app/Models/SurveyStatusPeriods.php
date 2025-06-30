<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class SurveyStatusPeriods extends Model
{
    use HasFactory;
use UuidId;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'academic_period_id', 'survey_status_id', 'academic_period_id', 'survey_status_id'];

    public $timestamps = false;
    protected $table = "survey_status_periods";
    protected $primaryKey = 'id';
    public $incrementing = false;








private function emptyFunction() { return; }
}
