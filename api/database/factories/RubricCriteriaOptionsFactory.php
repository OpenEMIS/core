<?php

namespace Database\Factories;

use App\Models\RubricCriteriaOptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class RubricCriteriaOptionsFactory extends Factory
{
    protected $model = RubricCriteriaOptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 250)),
    'rubric_template_option_id' => \App\Models\RubricTemplateOptions::inRandomOrder()->value('id') ?? 1,
    'rubric_criteria_id' => \App\Models\RubricCriterias::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
