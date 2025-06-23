<?php

namespace Database\Factories\Api5;

use App\Models\Api5\CustomFieldTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CustomFieldTypesFactory extends Factory
{
    protected $model = CustomFieldTypes::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 100)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'value' => $this->faker->lexify(str_repeat("?", 100)),
    'description' => $this->faker->text(50),
    'format' => $this->faker->lexify(str_repeat("?", 50)),
    'is_mandatory' => $this->faker->numberBetween(0, 1),
    'is_unique' => $this->faker->numberBetween(0, 1),
    'visible' => $this->faker->numberBetween(1, 1000),
];
    }
}
