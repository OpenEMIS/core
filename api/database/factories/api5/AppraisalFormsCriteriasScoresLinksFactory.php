<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AppraisalFormsCriteriasScoresLinks;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AppraisalFormsCriteriasScoresLinksFactory extends Factory
{
    protected $model = AppraisalFormsCriteriasScoresLinks::class;

    public function definition(): array
    {


        return [
    'appraisal_form_id' =>  \App\Models\AppraisalForms::factory()->create()->id,
    'appraisal_criteria_id' =>  \App\Models\AppraisalCriterias::factory()->create()->id,
    'appraisal_criteria_linked_id' =>  \App\Models\AppraisalCriterias::factory()->create()->id,
];
    }
}
