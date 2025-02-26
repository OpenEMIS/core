<?php

namespace Database\Factories;

use App\Models\AppraisalFormsCriteriasScoresLinks;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalFormsCriteriasScoresLinksFactory extends Factory
{
    protected $model = AppraisalFormsCriteriasScoresLinks::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $appraisal_form_id = $this->faker->randomElement(\App\Models\AppraisalFormsCriteriasScoresLinks::pluck('appraisal_form_id')->toArray()) ?? 1;
            $appraisal_criteria_id = $this->faker->randomElement(\App\Models\AppraisalFormsCriteriasScoresLinks::pluck('appraisal_criteria_id')->toArray()) ?? 1;
            $appraisal_criteria_linked_id = $this->faker->randomElement(\App\Models\AppraisalFormsCriteriasScoresLinks::pluck('appraisal_criteria_linked_id')->toArray()) ?? 1;
    $exists = AppraisalFormsCriteriasScoresLinks::where('appraisal_form_id', $appraisal_form_id)
                ->where('appraisal_criteria_id', $appraisal_criteria_id)
                ->where('appraisal_criteria_linked_id', $appraisal_criteria_linked_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'appraisal_form_id' => \App\Models\AppraisalForms::inRandomOrder()->value('id') ?? \App\Models\AppraisalForms::factory()->create()->id,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::inRandomOrder()->value('id') ?? \App\Models\AppraisalCriterias::factory()->create()->id,
    'appraisal_criteria_linked_id' => \App\Models\AppraisalCriterias::inRandomOrder()->value('id') ?? \App\Models\AppraisalCriterias::factory()->create()->id,
];
    }
}
