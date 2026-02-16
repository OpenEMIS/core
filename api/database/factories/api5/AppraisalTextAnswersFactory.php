<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AppraisalTextAnswers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalTextAnswersFactory extends Factory
{
    protected $model = AppraisalTextAnswers::class;

    public function definition(): array
    {


        return [
    'appraisal_form_id' =>  \App\Models\AppraisalForms::factory()->create()->id,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::factory()->create()->id,
    'institution_staff_appraisal_id' =>  \App\Models\InstitutionStaffAppraisals::factory()->create()->id,
    'answer' => $this->faker->text(50),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
