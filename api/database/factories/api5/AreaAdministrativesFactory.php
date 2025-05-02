<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AreaAdministratives;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AreaAdministrativesFactory extends Factory
{
    protected $model = AreaAdministratives::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'code' => $this->faker->lexify(str_repeat("?", 60)),
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'is_main_country' => $this->faker->numberBetween(0, 1),
    'parent_id' => $this->faker->numberBetween(1, 1000),
    'lft' => $this->faker->numberBetween(1, 1000),
    'rght' => $this->faker->numberBetween(1, 1000),
    'area_administrative_level_id' => \App\Models\AreaAdministrativeLevels::inRandomOrder()->value('id') ?? \App\Models\AreaAdministrativeLevels::factory()->create()->id,
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
