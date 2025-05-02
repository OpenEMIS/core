<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleTimeslots extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'interval', 'order', 'institution_schedule_interval_id', 'institution_schedule_interval_id'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








    public function instituteInterval()
    {
        return $this->belongsTo(InstitutionScheduleIntervals::class, 'institution_schedule_interval_id', 'id');
    }
}
