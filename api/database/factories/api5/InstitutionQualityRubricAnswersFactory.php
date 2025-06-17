<?php

namespace Database\Factories\Api5;

use App\Models\Api5\InstitutionQualityRubricAnswers;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class InstitutionQualityRubricAnswersFactory extends Factory
{
    protected $model = InstitutionQualityRubricAnswers::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'institution_quality_rubric_id' => \App\Models\InstitutionQualityRubrics::inRandomOrder()->value('id') ?? \App\Models\InstitutionQualityRubrics::factory()->create()->id,
    'rubric_section_id' => \App\Models\RubricSections::inRandomOrder()->value('id') ?? \App\Models\RubricSections::factory()->create()->id,
    'rubric_criteria_id' => \App\Models\RubricCriterias::inRandomOrder()->value('id') ?? \App\Models\RubricCriterias::factory()->create()->id,
    'rubric_criteria_option_id' => \App\Models\RubricCriteriaOptions::inRandomOrder()->value('id') ?? \App\Models\RubricCriteriaOptions::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
