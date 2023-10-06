<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTypes extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "staff_types";
}
