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
        $attempts = 0;
do {
    $education_subject_id = $this->faker->randomElement(\App\Models\AssessmentItemsGradingTypes::pluck('education_subject_id')->toArray()) ?? 1;
            $assessment_grading_type_id = $this->faker->randomElement(\App\Models\AssessmentItemsGradingTypes::pluck('assessment_grading_type_id')->toArray()) ?? 1;
            $assessment_id = $this->faker->randomElement(\App\Models\AssessmentItemsGradingTypes::pluck('assessment_id')->toArray()) ?? 1;
            $assessment_period_id = $this->faker->randomElement(\App\Models\AssessmentItemsGradingTypes::pluck('assessment_period_id')->toArray()) ?? 1;
    $exists = AssessmentItemsGradingTypes::where('education_subject_id', $education_subject_id)
                ->where('assessment_grading_type_id', $assessment_grading_type_id)
                ->where('assessment_id', $assessment_id)
                ->where('assessment_period_id', $assessment_period_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'education_subject_id' => \App\Models\EducationSubjects::inRandomOrder()->value('id') ?? 1,
    'assessment_grading_type_id' => \App\Models\AssessmentGradingTypes::inRandomOrder()->value('id') ?? 1,
    'assessment_id' => \App\Models\Assessments::inRandomOrder()->value('id') ?? 1,
    'assessment_period_id' => \App\Models\AssessmentPeriods::inRandomOrder()->value('id') ?? 1,
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
