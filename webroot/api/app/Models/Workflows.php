<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflows extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "workflows";
}
