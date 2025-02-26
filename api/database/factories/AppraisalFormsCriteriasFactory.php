<?php

namespace Database\Factories;

use App\Models\AppraisalFormsCriterias;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalFormsCriteriasFactory extends Factory
{
    protected $model = AppraisalFormsCriterias::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $appraisal_form_id = $this->faker->randomElement(\App\Models\AppraisalFormsCriterias::pluck('appraisal_form_id')->toArray()) ?? 1;
            $appraisal_criteria_id = $this->faker->randomElement(\App\Models\AppraisalFormsCriterias::pluck('appraisal_criteria_id')->toArray()) ?? 1;
    $exists = AppraisalFormsCriterias::where('appraisal_form_id', $appraisal_form_id)
                ->where('appraisal_criteria_id', $appraisal_criteria_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'appraisal_form_id' => \App\Models\AppraisalForms::inRandomOrder()->value('id') ?? 1,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::inRandomOrder()->value('id') ?? 1,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(1, 1000),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}