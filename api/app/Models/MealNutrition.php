<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealNutrition extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = "meal_nutritions";
}
