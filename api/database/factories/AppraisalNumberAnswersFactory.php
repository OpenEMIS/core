<?php

namespace Database\Factories;

use App\Models\AppraisalNumberAnswers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalNumberAnswersFactory extends Factory
{
    protected $model = AppraisalNumberAnswers::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $appraisal_form_id = $this->faker->randomElement(\App\Models\AppraisalNumberAnswers::pluck('appraisal_form_id')->toArray()) ?? 1;
            $appraisal_criteria_id = $this->faker->randomElement(\App\Models\AppraisalNumberAnswers::pluck('appraisal_criteria_id')->toArray()) ?? 1;
            $institution_staff_appraisal_id = $this->faker->randomElement(\App\Models\AppraisalNumberAnswers::pluck('institution_staff_appraisal_id')->toArray()) ?? 1;
    $exists = AppraisalNumberAnswers::where('appraisal_form_id', $appraisal_form_id)
                ->where('appraisal_criteria_id', $appraisal_criteria_id)
                ->where('institution_staff_appraisal_id', $institution_staff_appraisal_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'appraisal_form_id' => \App\Models\AppraisalForms::inRandomOrder()->value('id') ?? 1,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::inRandomOrder()->value('id') ?? 1,
    'institution_staff_appraisal_id' => \App\Models\InstitutionStaffAppraisals::inRandomOrder()->value('id') ?? 1,
    'answer' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
