<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// POCOR-7394-S

class AbsenceTypes extends Model
{
    use HasFactory;
    protected $table = "absence_types";
}
