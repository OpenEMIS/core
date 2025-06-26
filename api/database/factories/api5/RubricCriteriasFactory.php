<?php

namespace Database\Factories\Api5;

use App\Models\Api5\RubricCriterias;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RubricCriteriasFactory extends Factory
{
    protected $model = RubricCriterias::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'order' => $this->faker->numberBetween(1, 1000),
    'type' => $this->faker->numberBetween(1, 1000),
    'rubric_section_id' => \App\Models\RubricSections::inRandomOrder()->value('id') ?? \App\Models\RubricSections::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
