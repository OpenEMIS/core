<?php

namespace Database\Factories\Api5;

use App\Models\Api5\MealNutritionalRecords;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MealNutritionalRecordsFactory extends Factory
{
    protected $model = MealNutritionalRecords::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'meal_programmes_id' => \App\Models\MealProgrammes::inRandomOrder()->value('id') ?? \App\Models\MealProgrammes::factory()->create()->id,
    'nutritional_content_id' => \App\Models\MealNutritions::inRandomOrder()->value('id') ?? \App\Models\MealNutritions::factory()->create()->id,
];
    }
}
