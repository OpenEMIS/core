<?php

namespace Database\Factories;

use App\Models\SummaryInstitutionStudentAbsences;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryInstitutionStudentAbsencesFactory extends Factory
{
    protected $model = SummaryInstitutionStudentAbsences::class;

    public function definition(): array
    {


        return [
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_code' => $this->faker->lexify(str_repeat("?", 150)),
    'institution_name' => $this->faker->lexify(str_repeat("?", 150)),
    'area_id' => $this->faker->numberBetween(1, 1000),
    'area_code' => $this->faker->lexify(str_repeat("?", 150)),
    'area_name' => $this->faker->lexify(str_repeat("?", 150)),
    'area_administrative_id' => $this->faker->numberBetween(1, 1000),
    'area_administrative_code' => $this->faker->lexify(str_repeat("?", 150)),
    'area_administrative_name' => $this->faker->lexify(str_repeat("?", 150)),
    'student_id' => $this->faker->numberBetween(1, 1000),
    'openemis_no' => $this->faker->lexify(str_repeat("?", 150)),
    'default_identity_number' => $this->faker->lexify(str_repeat("?", 150)),
    'student_name' => $this->faker->lexify(str_repeat("?", 300)),
    'enrol_start_date' => $this->faker->lexify(str_repeat("?", 150)),
    'enrol_end_date' => $this->faker->lexify(str_repeat("?", 150)),
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_code' => $this->faker->lexify(str_repeat("?", 150)),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 150)),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'education_grade_code' => $this->faker->lexify(str_repeat("?", 150)),
    'education_grade_name' => $this->faker->lexify(str_repeat("?", 150)),
    'absent_date' => $this->faker->lexify(str_repeat("?", 150)),
    'absent_days' => $this->faker->lexify(str_repeat("?", 150)),
    'absence_subject_period' => $this->faker->lexify(str_repeat("?", 150)),
    'absence_type_id' => $this->faker->numberBetween(1, 1000),
    'absence_type' => $this->faker->lexify(str_repeat("?", 150)),
    'student_absence_reason_id' => $this->faker->numberBetween(1, 1000),
    'student_absence_reasons' => $this->faker->lexify(str_repeat("?", 150)),
    'student_status_id' => $this->faker->numberBetween(1, 1000),
    'student_status' => $this->faker->lexify(str_repeat("?", 150)),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
