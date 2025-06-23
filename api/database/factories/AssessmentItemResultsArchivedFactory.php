<?php

namespace Database\Factories;

use App\Models\AssessmentItemResultsArchived;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\EducationSubjects;

class AssessmentItemResultsArchivedFactory extends Factory
{
    protected $model = AssessmentItemResultsArchived::class;

    public function definition(): array
    {


        return [
    'id' => (string) \Illuminate\Support\Str::uuid(),
    'marks' => $this->faker->randomFloat(2, 10, 1000),
    'assessment_grading_option_id' => $this->faker->numberBetween(1, 1000),
    'student_id' => $this->faker->numberBetween(1, 1000),
    'assessment_id' => $this->faker->numberBetween(1, 1000),
    'education_subject_id' => EducationSubjects::factory()->create()->id,
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'assessment_period_id' => $this->faker->numberBetween(1, 1000),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_classes_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
