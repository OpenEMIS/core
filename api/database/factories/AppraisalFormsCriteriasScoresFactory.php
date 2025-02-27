<?php

namespace Database\Factories;

use App\Models\AppraisalFormsCriteriasScores;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalFormsCriteriasScoresFactory extends Factory
{
    protected $model = AppraisalFormsCriteriasScores::class;

    public function definition(): array
    {


        return [
    'appraisal_form_id' => \App\Models\AppraisalForms::factory()->create()->id,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::factory()->create()->id,
    'final_score' => $this->faker->numberBetween(1, 1000),
    'params' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
