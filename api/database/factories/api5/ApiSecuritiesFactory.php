<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ApiSecurities;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ApiSecuritiesFactory extends Factory
{
    protected $model = ApiSecurities::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::getNextId(),
    'name' => $this->faker->lexify(str_repeat("?", 255)),
    'model' => $this->faker->lexify(str_repeat("?", 255)),
    'index' => $this->faker->numberBetween(0, 1),
    'view' => $this->faker->numberBetween(0, 1),
    'add' => $this->faker->numberBetween(0, 1),
    'edit' => $this->faker->numberBetween(0, 1),
    'delete' => $this->faker->numberBetween(0, 1),
    'execute' => $this->faker->numberBetween(0, 1),
];
    }
}
