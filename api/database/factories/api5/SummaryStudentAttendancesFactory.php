<?php

namespace Database\Factories\Api5;

use App\Models\Api5\SummaryStudentAttendances;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryStudentAttendancesFactory extends Factory
{
    protected $model = SummaryStudentAttendances::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_code' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'education_grade_code' => $this->faker->lexify(str_repeat("?", 150)),
    'education_grade_name' => $this->faker->lexify(str_repeat("?", 150)),
    'class_id' => $this->faker->numberBetween(1, 1000),
    'class_name' => $this->faker->lexify(str_repeat("?", 150)),
    'attendance_date' => \Carbon\Carbon::now()->format("Y-m-d"),
    'period_id' => $this->faker->numberBetween(1, 1000),
    'period_name' => $this->faker->lexify(str_repeat("?", 70)),
    'subject_id' => $this->faker->numberBetween(1, 1000),
    'subject_name' => $this->faker->lexify(str_repeat("?", 150)),
    'female_count' => $this->faker->numberBetween(1, 1000),
    'male_count' => $this->faker->numberBetween(1, 1000),
    'total_count' => $this->faker->numberBetween(1, 1000),
    'marked_attendance' => $this->faker->numberBetween(1, 1000),
    'unmarked_attendance' => $this->faker->numberBetween(1, 1000),
    'present_female_count' => $this->faker->numberBetween(1, 1000),
    'present_male_count' => $this->faker->numberBetween(1, 1000),
    'present_total_count' => $this->faker->numberBetween(1, 1000),
    'absent_female_count' => $this->faker->numberBetween(1, 1000),
    'absent_male_count' => $this->faker->numberBetween(1, 1000),
    'absent_total_count' => $this->faker->numberBetween(1, 1000),
    'late_female_count' => $this->faker->numberBetween(1, 1000),
    'late_male_count' => $this->faker->numberBetween(1, 1000),
    'late_total_count' => $this->faker->numberBetween(1, 1000),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
