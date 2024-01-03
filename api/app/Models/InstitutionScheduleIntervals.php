<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleIntervals extends Model
{
    use HasFactory;

    public function shift()
    {
        return $this->belongsTo(InstitutionShifts::class, 'institution_shift_id', 'id');
    }
}