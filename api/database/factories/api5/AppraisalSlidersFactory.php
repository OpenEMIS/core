<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AppraisalSliders;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalSlidersFactory extends Factory
{
    protected $model = AppraisalSliders::class;

    public function definition(): array
    {


        return [
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::factory()->create()->id,
    'min' => $this->faker->randomFloat(2, 10, 1000),
    'max' => $this->faker->randomFloat(2, 10, 1000),
    'step' => $this->faker->randomFloat(2, 10, 1000),
];
    }
}
