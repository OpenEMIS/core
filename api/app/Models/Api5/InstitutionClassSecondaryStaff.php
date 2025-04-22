<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClassSecondaryStaff extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_classes_secondary_staff";

    public function institutionClass()
    {
        return $this->belongsTo(InstitutionClasses::class);
    }
}
