<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClassSecondaryStaff extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_classes_secondary_staff";
}
