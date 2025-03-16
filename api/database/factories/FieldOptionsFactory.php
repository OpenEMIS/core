<?php

namespace Database\Factories;

use App\Models\FieldOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FieldOptionsFactory extends Factory
{
    protected $model = FieldOptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 200)),
    'category' => $this->faker->lexify(str_repeat("?", 100)),
    'table_name' => $this->faker->lexify(str_repeat("?", 200)),
    'order' => $this->faker->numberBetween(1, 1000),
    'modified_by' => $this->faker->lexify(str_repeat("?", 10)),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_by' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
