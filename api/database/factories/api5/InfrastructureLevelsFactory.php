<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InfrastructureLevels;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InfrastructureLevelsFactory extends Factory
{
    protected $model = InfrastructureLevels::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 100)),
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'description' => $this->faker->text(50),
    'editable' => $this->faker->numberBetween(1, 1000),
    'parent_id' => $this->faker->numberBetween(1, 1000),
    'lft' => $this->faker->numberBetween(1, 1000),
    'rght' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
