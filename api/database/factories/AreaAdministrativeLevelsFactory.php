<?php

namespace Database\Factories;

use App\Models\AreaAdministrativeLevels;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AreaAdministrativeLevelsFactory extends Factory
{
    protected $model = AreaAdministrativeLevels::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'level' => $this->faker->numberBetween(1, 1000),
    'area_administrative_id' => \App\Models\AreaAdministratives::inRandomOrder()->value('id') ?? \App\Models\AreaAdministratives::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
