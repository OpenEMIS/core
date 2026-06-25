<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionScheduleIntervals extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'academic_period_id', 'institution_id', 'institution_shift_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'institution_id', 'institution_shift_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;








    public function shift()
    {
        return $this->belongsTo(InstitutionShifts::class, 'institution_shift_id', 'id');
    }
}
