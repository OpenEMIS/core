<?php

namespace Database\Factories;

use App\Models\SummaryInstitutionStudentSubjectResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryInstitutionStudentSubjectResultsFactory extends Factory
{
    protected $model = SummaryInstitutionStudentSubjectResults::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 200)),
    'area_id' => $this->faker->numberBetween(1, 1000),
    'area_code' => $this->faker->lexify(str_repeat("?", 200)),
    'area_name' => $this->faker->lexify(str_repeat("?", 200)),
    'area_administrative_id' => $this->faker->numberBetween(1, 1000),
    'area_administrative_code' => $this->faker->lexify(str_repeat("?", 200)),
    'area_administrative_name' => $this->faker->lexify(str_repeat("?", 200)),
    'institution_provider_id' => $this->faker->numberBetween(1, 1000),
    'institution_provider_name' => $this->faker->lexify(str_repeat("?", 200)),
    'institution_ownership_id' => $this->faker->numberBetween(1, 1000),
    'institution_ownership_name' => $this->faker->lexify(str_repeat("?", 200)),
    'institution_gender_id' => $this->faker->numberBetween(1, 1000),
    'institution_gender_name' => $this->faker->lexify(str_repeat("?", 200)),
    'institution_id' => $this->faker->numberBetween(1, 1000),
    'institution_code' => $this->faker->lexify(str_repeat("?", 200)),
    'institution_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'education_grade_code' => $this->faker->lexify(str_repeat("?", 200)),
    'education_grade_name' => $this->faker->lexify(str_repeat("?", 200)),
    'student_id' => $this->faker->numberBetween(1, 1000),
    'student_openemis_no' => $this->faker->lexify(str_repeat("?", 200)),
    'student_first_name' => $this->faker->lexify(str_repeat("?", 200)),
    'student_middle_name' => $this->faker->lexify(str_repeat("?", 200)),
    'student_third_name' => $this->faker->lexify(str_repeat("?", 200)),
    'student_last_name' => $this->faker->lexify(str_repeat("?", 200)),
    'student_gender_id' => $this->faker->numberBetween(1, 1000),
    'student_gender_name' => $this->faker->lexify(str_repeat("?", 200)),
    'student_default_identity_id' => $this->faker->numberBetween(1, 1000),
    'student_default_identity_type' => $this->faker->lexify(str_repeat("?", 200)),
    'student_default_identity_number' => $this->faker->lexify(str_repeat("?", 200)),
    'student_default_nationality_id' => $this->faker->numberBetween(1, 1000),
    'student_default_nationality_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_subject_id' => $this->faker->numberBetween(1, 1000),
    'education_subject_code' => $this->faker->lexify(str_repeat("?", 200)),
    'education_subject_name' => $this->faker->lexify(str_repeat("?", 200)),
    'total_avg_results' => $this->faker->lexify(str_repeat("?", 200)),
    'male_avg_results' => $this->faker->lexify(str_repeat("?", 200)),
    'female_avg_results' => $this->faker->lexify(str_repeat("?", 200)),
    'created' => \Carbon\Carbon::now()->format("Y-m-d H:i:s"),
];
    }
}
