<?php

namespace Database\Factories\Api5;

use App\Models\Api5\MealFoodRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MealFoodRecordsFactory extends Factory
{
    protected $model = MealFoodRecords::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'meal_programmes_id' => \App\Models\MealProgrammes::inRandomOrder()->value('id') ?? \App\Models\MealProgrammes::factory()->create()->id,
    'food_type_id' => \App\Models\FoodTypes::inRandomOrder()->value('id') ?? \App\Models\FoodTypes::factory()->create()->id,
];
    }
}
