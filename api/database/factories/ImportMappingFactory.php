<?php

namespace Database\Factories;

use App\Models\ImportMapping;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImportMappingFactory extends Factory
{
    protected $model = ImportMapping::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'model' => $this->faker->lexify(str_repeat("?", 50)),
    'column_name' => $this->faker->lexify(str_repeat("?", 50)),
    'description' => $this->faker->lexify(str_repeat("?", 50)),
    'order' => $this->faker->numberBetween(1, 1000),
    'is_optional' => $this->faker->numberBetween(0, 1),
    'foreign_key' => $this->faker->numberBetween(1, 1000),
    'lookup_plugin' => $this->faker->lexify(str_repeat("?", 50)),
    'lookup_model' => $this->faker->lexify(str_repeat("?", 50)),
    'lookup_column' => $this->faker->lexify(str_repeat("?", 50)),
];
    }
}
