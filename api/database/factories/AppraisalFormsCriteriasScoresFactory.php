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
        $attempts = 0;
do {
    $appraisal_form_id = $this->faker->randomElement(\App\Models\AppraisalFormsCriteriasScores::pluck('appraisal_form_id')->toArray()) ?? 1;
            $appraisal_criteria_id = $this->faker->randomElement(\App\Models\AppraisalFormsCriteriasScores::pluck('appraisal_criteria_id')->toArray()) ?? 1;
    $exists = AppraisalFormsCriteriasScores::where('appraisal_form_id', $appraisal_form_id)
                ->where('appraisal_criteria_id', $appraisal_criteria_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'appraisal_form_id' => \App\Models\AppraisalForms::inRandomOrder()->value('id') ?? 1,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::inRandomOrder()->value('id') ?? 1,
    'final_score' => $this->faker->numberBetween(1, 1000),
    'params' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}