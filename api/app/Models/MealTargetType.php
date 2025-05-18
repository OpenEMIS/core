<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealTargetType extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "meal_target_types";
}
