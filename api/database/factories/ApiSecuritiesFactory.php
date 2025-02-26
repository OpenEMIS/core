<?php

namespace Database\Factories;

use App\Models\ApiSecurities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ApiSecuritiesFactory extends Factory
{
    protected $model = ApiSecurities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 255)),
    'model' => $this->faker->lexify(str_repeat("?", 255)),
    'index' => $this->faker->numberBetween(1, 1000),
    'view' => $this->faker->numberBetween(1, 1000),
    'add' => $this->faker->numberBetween(1, 1000),
    'edit' => $this->faker->numberBetween(1, 1000),
    'delete' => $this->faker->numberBetween(1, 1000),
    'execute' => $this->faker->numberBetween(1, 1000),
];
    }
}
