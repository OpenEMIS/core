<?php

namespace Database\Factories;

use App\Models\InstitutionMealProgrammes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionMealProgrammesFactory extends Factory
{
    protected $model = InstitutionMealProgrammes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'meal_programmes_id' => \App\Models\MealProgrammes::inRandomOrder()->value('id') ?? \App\Models\MealProgrammes::factory()->create()->id,
    'institution_id' => \App\Models\Institutions::inRandomOrder()->value('id') ?? \App\Models\Institutions::factory()->create()->id,
    'date_received' => \Carbon\Carbon::now()->format("Y-m-d"),
    'quantity_received' => $this->faker->numberBetween(1, 1000),
    'delivery_status_id' => \App\Models\MealStatusTypes::inRandomOrder()->value('id') ?? \App\Models\MealStatusTypes::factory()->create()->id,
    'comment' => $this->faker->lexify(str_repeat("?", 50)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'meal_rating_id' => \App\Models\MealRatings::inRandomOrder()->value('id') ?? \App\Models\MealRatings::factory()->create()->id,
];
    }
}
