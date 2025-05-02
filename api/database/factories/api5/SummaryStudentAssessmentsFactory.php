<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SummaryStudentAssessments;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryStudentAssessmentsFactory extends Factory
{
    protected $model = SummaryStudentAssessments::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_code' => $this->faker->lexify(str_repeat("?", 100)),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 100)),
    'area_id' => $this->faker->numberBetween(1, 1000),
    'area_code' => $this->faker->lexify(str_repeat("?", 100)),
    'area_name' => $this->faker->lexify(str_repeat("?", 100)),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_code' => $this->faker->lexify(str_repeat("?", 100)),
    'institution_name' => $this->faker->lexify(str_repeat("?", 100)),
    'grade_id' => $this->faker->numberBetween(1, 1000),
    'grade_code' => $this->faker->lexify(str_repeat("?", 100)),
    'grade_name' => $this->faker->lexify(str_repeat("?", 100)),
    'institution_classes_id' => $this->faker->numberBetween(1, 1000),
    'institution_classes_name' => $this->faker->lexify(str_repeat("?", 100)),
    'homeroom_teacher_id' => $this->faker->numberBetween(1, 1000),
    'homeroom_teacher_name' => $this->faker->lexify(str_repeat("?", 250)),
    'subject_id' => $this->faker->numberBetween(1, 1000),
    'subject_code' => $this->faker->lexify(str_repeat("?", 100)),
    'subject_name' => $this->faker->lexify(str_repeat("?", 100)),
    'subject_weight' => $this->faker->randomFloat(2, 10, 1000),
    'assessment_id' => $this->faker->numberBetween(1, 1000),
    'assessment_code' => $this->faker->lexify(str_repeat("?", 100)),
    'assessment_name' => $this->faker->lexify(str_repeat("?", 100)),
    'period_id' => $this->faker->numberBetween(1, 1000),
    'period_code' => $this->faker->lexify(str_repeat("?", 100)),
    'period_name' => $this->faker->lexify(str_repeat("?", 100)),
    'academic_term' => $this->faker->lexify(str_repeat("?", 100)),
    'period_weight' => $this->faker->randomFloat(2, 10, 1000),
    'student_id' => $this->faker->numberBetween(1, 1000),
    'student_name' => $this->faker->lexify(str_repeat("?", 250)),
    'latest_mark' => $this->faker->randomFloat(2, 10, 1000),
    'total_mark' => $this->faker->randomFloat(2, 10, 1000),
    'average_mark' => $this->faker->randomFloat(2, 10, 1000),
    'institution_average_mark' => $this->faker->randomFloat(2, 10, 1000),
    'area_average_mark' => $this->faker->randomFloat(2, 10, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
