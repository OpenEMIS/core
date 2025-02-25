<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomModules extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "custom_modules";

}
