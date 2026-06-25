<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SummaryGradeStatusGenders;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryGradeStatusGendersFactory extends Factory
{
    protected $model = SummaryGradeStatusGenders::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_system_id' => $this->faker->numberBetween(1, 1000),
    'education_system_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_level_isced_id' => $this->faker->numberBetween(1, 1000),
    'education_level_isced_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_level_isced_level' => $this->faker->numberBetween(1, 1000),
    'education_level_id' => $this->faker->numberBetween(1, 1000),
    'education_level_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_cycle_id' => $this->faker->numberBetween(1, 1000),
    'education_cycle_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_programme_id' => $this->faker->numberBetween(1, 1000),
    'education_programme_code' => $this->faker->lexify(str_repeat("?", 150)),
    'education_programme_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'education_grade_code' => $this->faker->lexify(str_repeat("?", 150)),
    'education_grade_name' => $this->faker->lexify(str_repeat("?", 150)),
    'student_gender_id' => $this->faker->numberBetween(1, 1000),
    'student_gender_name' => $this->faker->lexify(str_repeat("?", 150)),
    'student_status_id' => $this->faker->numberBetween(1, 1000),
    'student_status_name' => $this->faker->lexify(str_repeat("?", 150)),
    'total_students' => $this->faker->numberBetween(1, 1000),
];
    }
}
