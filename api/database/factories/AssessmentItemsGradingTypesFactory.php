<?php

namespace Database\Factories;

use App\Models\AssessmentItemsGradingTypes;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AssessmentItemsGradingTypesFactory extends Factory
{
    protected $model = AssessmentItemsGradingTypes::class;

    public function definition(): array
    {
        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'education_subject_id' => \App\Models\EducationSubjects::factory()->create()->id,
    'assessment_grading_type_id' => \App\Models\AssessmentGradingTypes::factory()->create()->id,
    'assessment_id' => \App\Models\Assessments::factory()->create()->id,
    'assessment_period_id' =>  \App\Models\AssessmentPeriods::factory()->create()->id,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
