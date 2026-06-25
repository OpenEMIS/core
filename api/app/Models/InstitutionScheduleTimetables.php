<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionScheduleTimetables extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'status', 'academic_period_id', 'institution_class_id', 'institution_id', 'institution_schedule_interval_id', 'institution_schedule_term_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'institution_class_id', 'institution_id', 'institution_schedule_interval_id', 'institution_schedule_term_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








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
        return $this->belongsTo(InstitutionScheduleTerms::class, 'institution_schedule_term_id', 'id');
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }


    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

}
