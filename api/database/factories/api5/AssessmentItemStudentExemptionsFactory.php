<?php

namespace Database\Factories\Api5;

use App\Models\Api5\AssessmentItemStudentExemptions;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AssessmentItemStudentExemptionsFactory extends Factory
{
    protected $model = AssessmentItemStudentExemptions::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'assessment_id' => $this->faker->numberBetween(1, 1000),
    'education_subject_id' => $this->faker->numberBetween(1, 1000),
    'student_id' => $this->faker->numberBetween(1, 1000),
    'institution_class_id' => $this->faker->numberBetween(1, 1000),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'assessment_period_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
