<?php

namespace Database\Factories;

use App\Models\ExaminationGradingOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationGradingOptionsFactory extends Factory
{
    protected $model = ExaminationGradingOptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 80)),
    'description' => $this->faker->text(50),
    'min' => $this->faker->randomFloat(2, 10, 1000),
    'max' => $this->faker->randomFloat(2, 10, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'examination_grading_type_id' => \App\Models\ExaminationGradingTypes::inRandomOrder()->value('id') ?? \App\Models\ExaminationGradingTypes::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
