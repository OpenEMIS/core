<?php

namespace Database\Factories;

use App\Models\AssessmentItemResultsArchived;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AssessmentItemResultsArchivedFactory extends Factory
{
    protected $model = AssessmentItemResultsArchived::class;

    public function definition(): array
    {
        $attempts = 0;
do {
    $student_id = $this->faker->randomElement(\App\Models\AssessmentItemResultsArchived::pluck('student_id')->toArray()) ?? 1;
            $assessment_id = $this->faker->randomElement(\App\Models\AssessmentItemResultsArchived::pluck('assessment_id')->toArray()) ?? 1;
            $education_subject_id = $this->faker->randomElement(\App\Models\AssessmentItemResultsArchived::pluck('education_subject_id')->toArray()) ?? 1;
            $education_grade_id = $this->faker->randomElement(\App\Models\AssessmentItemResultsArchived::pluck('education_grade_id')->toArray()) ?? 1;
            $academic_period_id = $this->faker->randomElement(\App\Models\AssessmentItemResultsArchived::pluck('academic_period_id')->toArray()) ?? 1;
            $assessment_period_id = $this->faker->randomElement(\App\Models\AssessmentItemResultsArchived::pluck('assessment_period_id')->toArray()) ?? 1;
            $institution_classes_id = $this->faker->randomElement(\App\Models\AssessmentItemResultsArchived::pluck('institution_classes_id')->toArray()) ?? 1;
    $exists = AssessmentItemResultsArchived::where('student_id', $student_id)
                ->where('assessment_id', $assessment_id)
                ->where('education_subject_id', $education_subject_id)
                ->where('education_grade_id', $education_grade_id)
                ->where('academic_period_id', $academic_period_id)
                ->where('assessment_period_id', $assessment_period_id)
                ->where('institution_classes_id', $institution_classes_id)
        ->exists();
    $attempts++;
} while ($exists && $attempts < 5);

        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'marks' => $this->faker->randomFloat(2, 10, 1000),
    'assessment_grading_option_id' => $this->faker->numberBetween(1, 1000),
    'student_id' => $this->faker->numberBetween(1, 1000),
    'assessment_id' => $this->faker->numberBetween(1, 1000),
    'education_subject_id' => $this->faker->numberBetween(1, 1000),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'assessment_period_id' => $this->faker->numberBetween(1, 1000),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_classes_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 1000),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}