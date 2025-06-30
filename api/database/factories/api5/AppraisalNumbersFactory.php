<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AppraisalNumbers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalNumbersFactory extends Factory
{
    protected $model = AppraisalNumbers::class;

    public function definition(): array
    {


        return [
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::factory()->create()->id,
    'min_inclusive' => $this->faker->numberBetween(1, 1000),
    'max_inclusive' => $this->faker->numberBetween(1, 1000),
    'min_exclusive' => $this->faker->numberBetween(1, 1000),
    'max_exclusive' => $this->faker->numberBetween(1, 1000),
    'validation_rule' => $this->faker->lexify(str_repeat("?", 50)),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
