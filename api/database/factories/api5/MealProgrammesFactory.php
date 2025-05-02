<?php

namespace Database\Factories\Api5;

use App\Models\Api5\MealProgrammes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MealProgrammesFactory extends Factory
{
    protected $model = MealProgrammes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'academic_period_id' => \App\Models\AcademicPeriods::inRandomOrder()->value('id') ?? \App\Models\AcademicPeriods::factory()->create()->id,
    'name' => $this->faker->lexify(str_repeat("?", 50)),
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'type' => $this->faker->numberBetween(1, 1000),
    'targeting' => $this->faker->numberBetween(1, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'amount' => $this->faker->randomFloat(2, 10, 1000),
    'implementer' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
