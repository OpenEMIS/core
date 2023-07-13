<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionLocalities extends Model
{
    use HasFactory;


    public $timestamps = false;
    protected $table = "institution_localities";
}
