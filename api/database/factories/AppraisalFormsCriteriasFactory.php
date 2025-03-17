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


        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'appraisal_form_id' => \App\Models\AppraisalForms::factory()->create()->id,
    'appraisal_criteria_id' => \App\Models\AppraisalCriterias::factory()->create()->id,
    'section' => $this->faker->lexify(str_repeat("?", 250)),
    'is_mandatory' => $this->faker->numberBetween(0, 1),
    'order' => $this->faker->numberBetween(1, 1000),
];
    }
}
