<?php

namespace Database\Factories\Api5;

use App\Models\Api5\EducationGradesGpa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EducationGradesGpaFactory extends Factory
{
    protected $model = EducationGradesGpa::class;

    public function definition(): array
    {


        return [
    'id' => $this->model::max('id') + 1,
    'name' => $this->faker->lexify(str_repeat("?", 100)),
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'start_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'end_date' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'gpa_grading_type_id' => $this->faker->numberBetween(1, 1000),
    'modified_user_id' => $this->faker->numberBetween(1, 2),
    'modified' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
    'created_user_id' => $this->faker->numberBetween(1, 2),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
