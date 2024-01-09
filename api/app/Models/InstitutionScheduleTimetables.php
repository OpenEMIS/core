<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleTimetables extends Model
{
    use HasFactory;

    public function institutionClass()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }

    public function scheduleInterval()
    {
        return $this->belongsTo(InstitutionScheduleIntervals::class, 'institution_schedule_interval_id', 'id');
    }

    public function scheduleTerm()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_schedule_term_id', 'id');
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }
}