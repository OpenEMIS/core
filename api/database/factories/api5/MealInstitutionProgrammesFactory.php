<?php

namespace Database\Factories\Api5;

use App\Models\Api5\MealInstitutionProgrammes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MealInstitutionProgrammesFactory extends Factory
{
    protected $model = MealInstitutionProgrammes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'meal_programme_id' => \App\Models\MealProgrammes::inRandomOrder()->value('id') ?? \App\Models\MealProgrammes::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'area_id' => $this->faker->numberBetween(1, 1000),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
