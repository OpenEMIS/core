<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionOwnerships extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "institution_ownerships";
}
