<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionShifts extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_shifts";

    public function shiftOption()
    {
        return $this->belongsTo(ShiftOptions::class, 'shift_option_id', 'id');
    }
}
