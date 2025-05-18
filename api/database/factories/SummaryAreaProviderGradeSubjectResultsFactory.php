<?php

namespace Database\Factories;

use App\Models\SummaryAreaProviderGradeSubjectResults;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SummaryAreaProviderGradeSubjectResultsFactory extends Factory
{
    protected $model = SummaryAreaProviderGradeSubjectResults::class;

    public function definition(): array
    {


        return [
    'academic_period_id' => $this->faker->numberBetween(1, 1000),
    'academic_period_name' => $this->faker->lexify(str_repeat("?", 200)),
    'area_id' => $this->faker->numberBetween(1, 1000),
    'area_code' => $this->faker->lexify(str_repeat("?", 200)),
    'area_name' => $this->faker->lexify(str_repeat("?", 200)),
    'institution_provider_id' => $this->faker->numberBetween(1, 1000),
    'institution_provider_name' => $this->faker->lexify(str_repeat("?", 200)),
    'education_grade_id' => $this->faker->numberBetween(1, 1000),
    'education_grade_code' => $this->faker->lexify(str_repeat("?", 200)),
    'education_grade_name' => $this->faker->lexify(str_repeat("?", 200)),
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
