<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InstitutionShifts extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'start_time', 'end_time', 'academic_period_id', 'institution_id', 'location_institution_id', 'shift_option_id', 'previous_shift_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'academic_period_id', 'institution_id', 'location_institution_id', 'shift_option_id', 'previous_shift_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_shifts";








    public function shiftOption()
    {
        return $this->belongsTo(ShiftOptions::class, 'shift_option_id', 'id');
    }

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }
}
