<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ExaminationGradingTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ExaminationGradingTypesFactory extends Factory
{
    protected $model = ExaminationGradingTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 50)),
    'name' => $this->faker->lexify(str_repeat("?", 80)),
    'pass_mark' => $this->faker->randomFloat(2, 10, 1000),
    'max' => $this->faker->randomFloat(2, 10, 1000),
    'result_type' => $this->faker->lexify(str_repeat("?", 20)),
    'visible' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
