<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealRating extends Model
{
    use HasFactory;

        public $timestamps = false;
        protected $table = "meal_ratings";
}
