<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleTimeslots extends Model
{
    use HasFactory;

    public function instituteInterval()
    {
        return $this->belongsTo(InstitutionScheduleIntervals::class, 'institution_schedule_interval_id', 'id');
    }
}