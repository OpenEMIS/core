<?php

namespace Database\Factories\Api5;

use App\Models\Api5\ConfigItemOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ConfigItemOptionsFactory extends Factory
{
    protected $model = ConfigItemOptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'option_type' => $this->faker->lexify(str_repeat("?", 50)),
    'option' => $this->faker->lexify(str_repeat("?", 100)),
    'value' => $this->faker->lexify(str_repeat("?", 100)),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
];
    }
}
