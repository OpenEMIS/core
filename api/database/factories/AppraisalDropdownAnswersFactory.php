<?php

namespace Database\Factories;

use App\Models\AppraisalDropdownAnswers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalDropdownAnswersFactory extends Factory
{
    protected $model = AppraisalDropdownAnswers::class;

    public function definition(): array
    {


        return [
    'appraisal_form_id' => \App\Models\AppraisalForms::factory()->create()->id,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::factory()->create()->id,
    'institution_staff_appraisal_id' => \App\Models\InstitutionStaffAppraisals::factory()->create()->id,
    'answer' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
