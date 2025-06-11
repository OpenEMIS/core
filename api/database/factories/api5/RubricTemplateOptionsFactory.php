<?php

namespace Database\Factories\Api5;

use App\Models\Api5\RubricTemplateOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RubricTemplateOptionsFactory extends Factory
{
    protected $model = RubricTemplateOptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'weighting' => $this->faker->numberBetween(1, 1000),
    'color' => $this->faker->lexify(str_repeat("?", 10)),
    'order' => $this->faker->numberBetween(1, 1000),
    'rubric_template_id' => \App\Models\RubricTemplates::inRandomOrder()->value('id') ?? \App\Models\RubricTemplates::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
