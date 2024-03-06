<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuardianRelation extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "guardian_relations";
}
