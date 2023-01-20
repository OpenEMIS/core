<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionStudent extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = "institution_students";

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

}
