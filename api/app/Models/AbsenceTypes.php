<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// POCOR-7394-S

class AbsenceTypes extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;
    protected $table = "absence_types";

}
