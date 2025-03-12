<?php

namespace Database\Factories;

use App\Models\EducationLevels;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationLevelsFactory extends Factory
{
    protected $model = EducationLevels::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 150)),
    'order' => $this->faker->numberBetween(1, 1000),
    'visible' => $this->faker->numberBetween(1, 1000),
    'education_system_id' => \App\Models\EducationSystems::inRandomOrder()->value('id') ?? \App\Models\EducationSystems::factory()->create()->id,
    'education_level_isced_id' => \App\Models\EducationLevelIsced::inRandomOrder()->value('id') ?? \App\Models\EducationLevelIsced::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
